<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function count;

class AddRankCommand extends Command {

	public function __construct(){
		parent::__construct(
			'addrank',
			TF::LIGHT_PURPLE . 'Create a new rank! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/addrank <rank>'
		);

		$this->setPermission('velvet.ranks.edit');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if(!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1) {
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$result = PermissionManager::getInstance()->addRank($args[0]);
		$sender->sendMessage($result === PermissionManager::SUCCESS ? Translator::RANKS_PREFIX . TF::GREEN . 'Successfully created the rank ' . TF::YELLOW . $args[0] . '!' : ($result === PermissionManager::ALREADY_EXISTS ? Translator::RANKS_PREFIX . TF::RED . 'That rank already exists!' : TF::RED . Translator::RANKS_PREFIX . 'That name is invalid!'));
	}

}