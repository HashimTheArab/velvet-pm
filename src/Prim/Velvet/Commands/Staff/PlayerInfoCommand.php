<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function count;
use function yaml_parse_file;

class PlayerInfoCommand extends Command{

	public function __construct(){
		parent::__construct(
			'playerinfo',
			TF::LIGHT_PURPLE . 'View information about a player!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/playerinfo <player>'
		);
		$this->setAliases(['pinfo']);
		$this->setPermission('velvet.playerinfo');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);
		if(!$target instanceof VelvetPlayer || !$target->spawned){
			$sender->sendMessage(TF::RED . "$args[0] is not online!");
			return;
		}

		$models = yaml_parse_file(Main::getMain()->getDataFolder() . 'models.yml');
		$sender->sendMessage(TF::GREEN . TF::BOLD . '===' . TF::GREEN . 'PlayerInfo' . TF::GREEN . TF::BOLD . '===');
		$sender->sendMessage(
			TF::AQUA . 'Name: ' . TF::RED . "{$target->getName()}\n"
			. TF::AQUA . 'Ping: ' . TF::RED . $target->getNetworkSession()->getPing() . "ms\n"
			. TF::AQUA . 'OS: ' . TF::RED . Translator::SYSTEMS[$target->deviceOS] . "\n"
			. TF::AQUA . 'Controls: ' . TF::RED . Translator::CONTROLS[$target->inputMode] . "\n"
			. TF::AQUA . 'Model: ' . TF::RED . ($models[$target->deviceModel] ?? "Unknown\n")
			. TF::GREEN . TF::BOLD . '================'
		);
	}
}