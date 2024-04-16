<?php

namespace Prim\Velvet\Commands\World\SubCommands;

use pocketmine\command\CommandSender;
use Prim\Velvet\Commands\SubCommand;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;

class WorldLoadSubcommand extends SubCommand {

	public function __construct(){
		parent::__construct(TF::RED . 'Usage: ' . TF::GRAY . '/mw load <name>');
	}

	public function executeSub(CommandSender $sender, array $args) : void {
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		if(!$sender->getServer()->getWorldManager()->isWorldGenerated($args[0])){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::RED . 'That world does not exist!');
			return;
		}

		if($sender->getServer()->getWorldManager()->isWorldLoaded($args[0])){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::GREEN . 'That world is already loaded!');
			return;
		}

		if($sender->getServer()->getWorldManager()->loadWorld($args[0])){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::GREEN . 'Successfully loaded the world ' . TF::LIGHT_PURPLE . $args[0] . '!');
		} else {
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::RED . 'Failed to load the world ' . TF::LIGHT_PURPLE . $args[0] . '!');
		}
	}

}