<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;

class StatsCommand extends Command{

	public function __construct(){
		parent::__construct(
			"stats",
			TF::LIGHT_PURPLE . 'Check a players stats!',
			TF::RED . 'Usage: ' . TF::GRAY . '/stats <name>'
		);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);
		if(!$target instanceof VelvetPlayer){
			$sender->sendMessage(TF::RED . "$args[0] is not online!");
			return;
		}

		Main::getMain()->forms->statss($target, $sender);
	}
}