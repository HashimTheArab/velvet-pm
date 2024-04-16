<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function count;

class DelRankCommand extends Command {

	public function __construct(){
		parent::__construct(
			'delrank',
			TF::LIGHT_PURPLE . 'Delete a rank! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/delrank <rank>',
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

		$sender->sendMessage(PermissionManager::getInstance()->removeRank($args[0]) === PermissionManager::SUCCESS ? Translator::RANKS_PREFIX . TF::GREEN . 'You have deleted the rank ' . TF::YELLOW . $args[0] . '!' : Translator::RANKS_PREFIX . TF::RED . 'That rank does not exist!');
	}

}