<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function count;
use function is_null;

class StealSkinCommand extends Command {

	public function __construct(){
		parent::__construct(
			'stealskin',
			TF::LIGHT_PURPLE . 'Steal another players skin!',
			TF::RED . 'Usage: ' . TF::GRAY . '/stealskin <player>'
		);
		$this->setPermission('velvet.stealskin');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);
		if(is_null($target) || !$target->spawned){
			$sender->sendMessage(TF::RED . 'That player is not online!');
			return;
		}

		$sender->setSkin($target->getSkin());
		$sender->sendSkin();
	}

}