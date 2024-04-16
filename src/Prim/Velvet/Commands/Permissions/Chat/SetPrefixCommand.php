<?php

namespace Prim\Velvet\Commands\Permissions\Chat;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Translator;
use function count;

class SetPrefixCommand extends Command {

	public function __construct(){
		parent::__construct(
			'setprefix',
			TF::LIGHT_PURPLE . 'Set a players tag! ' . TF::GREEN . '(Staff!)',
			TF::RED . "Usage: " . TF::GRAY . "/setprefix <player> <tag>"
		);
		$this->setPermission('velvet.tags.manage');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 2){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$player = PermissionManager::getInstance()->getPlayer($args[0]);

		PermissionManager::getInstance()->setTag($player, $args[1]);
		$sender->sendMessage(TF::GRAY . 'You have set ' . TF::LIGHT_PURPLE . $player->getName() . '\'s' . TF::GRAY . ' tag to ' . TF::LIGHT_PURPLE . $args[1] . '!');
		if($player instanceof Player){
			$player->sendMessage(TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' has set your tag to ' . TF::LIGHT_PURPLE . $args[1] . '!');
			SessionManager::getInstance()->getSession($player)->tag = $args[1];
		}
	}
}