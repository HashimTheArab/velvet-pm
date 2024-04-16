<?php

namespace Prim\Velvet\Commands\World\SubCommands;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Commands\SubCommand;
use Prim\Velvet\Utils\Translator;
use function count;
use function is_null;

class WorldTeleportSubcommand extends SubCommand {

	public function __construct(){
		parent::__construct(TF::RED . 'Usage: ' . TF::GRAY . '/mw teleport <name> [player]');
	}

	public function executeSub(CommandSender $sender, array $args) : void {
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$target = $sender;
		if(count($args) > 1){
			if(is_null($sender->getServer()->getPlayerByPrefix($args[1]))){
				$sender->sendMessage(TF::RED . 'That player is not online!');
				return;
			}
			$target = $sender->getServer()->getPlayerByPrefix($args[1]);
		}

		if(!$target instanceof Player){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		$world = $sender->getServer()->getWorldManager()->getWorldByName($args[0]);
		if(is_null($world)){
			if(!$sender->getServer()->getWorldManager()->loadWorld($args[0])){
				$sender->sendMessage(Translator::WORLDS_PREFIX . TF::RED . 'That world does not exist!');
				return;
			}
			$world = $sender->getServer()->getWorldManager()->getWorldByName($args[0]);
		}

		$target->teleport($world->getSafeSpawn());
		if($target->getName() === $sender->getName()){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::GRAY . 'You were teleported to the world ' . TF::LIGHT_PURPLE . $world->getFolderName() . '!');
		} else {
			$target->sendMessage(Translator::WORLDS_PREFIX . TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' has teleported you to the world ' . TF::LIGHT_PURPLE . $world->getFolderName() . '!');
		}
	}

}