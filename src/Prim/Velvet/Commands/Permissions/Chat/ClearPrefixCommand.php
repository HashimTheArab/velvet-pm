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

class ClearPrefixCommand extends Command {

	public function __construct(){
		parent::__construct(
			'clearprefix',
			TF::LIGHT_PURPLE . 'Reset a players tag! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/clearprefix <player>'
		);
		$this->setPermission('velvet.tags.manage');
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

		$player = PermissionManager::getInstance()->getPlayer($args[0]);

		PermissionManager::getInstance()->setTag($player, null);
		$sender->sendMessage(TF::GRAY . 'You have reset ' . TF::LIGHT_PURPLE . $player->getName() . '\'s' . TF::GRAY . ' tag!');
		if($player instanceof Player){
			$player->sendMessage(TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' has' . TF::LIGHT_PURPLE . ' reset ' . TF::GRAY . 'your tag!');
			SessionManager::getInstance()->getSession($player)->tag = null;
		}
	}
}