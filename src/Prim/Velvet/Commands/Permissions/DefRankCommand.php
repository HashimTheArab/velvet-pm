<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function is_null;
use function count;

class DefRankCommand extends Command {

	public function __construct(){
		parent::__construct(
			'defrank',
			TF::LIGHT_PURPLE . 'Set the default rank! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/defrank <rank>'
		);

		$this->setPermission('velvet.ranks.edit');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if(!$sender->hasPermission($this->getPermission()) || ($sender instanceof Player && $sender->getXuid() !== Translator::OWNER_XUID)) {
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1) {
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$rank = PermissionManager::getInstance()->getRank($args[0]);
		if(is_null($rank)){
			$sender->sendMessage(Translator::RANK_DOESNT_EXIST);
			return;
		}

		PermissionManager::getInstance()->setDefaultRank($rank);
		$sender->sendMessage(Translator::RANKS_PREFIX . TF::GREEN . 'The default rank has been set to ' . TF::YELLOW . $args[0] . '!');
	}

}