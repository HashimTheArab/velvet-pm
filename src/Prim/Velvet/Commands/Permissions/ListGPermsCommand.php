<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function count;
use function implode;
use function array_chunk;
use function is_null;
use function is_numeric;

class ListGPermsCommand extends Command {

	public function __construct(){
		parent::__construct(
			'listgperms',
			TF::LIGHT_PURPLE . 'Shows a list of a ranks permissions! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/listgperms <rank> [page]'
		);

		$this->setPermission('velvet.ranks.view');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if(!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$rank = PermissionManager::getInstance()->getRank($args[0]);
		if(is_null($rank)) {
			$sender->sendMessage(Translator::RANK_DOESNT_EXIST);
			return;
		}

		$permissions = $rank->getPermissions();
		if(empty($permissions)) {
			$sender->sendMessage(TF::GREEN . "The rank {$rank->getName()} does not have any permissions!");
			return;
		}

		$pageHeight = $sender instanceof Player ? 6 : 24;
		$chunkedPermissions = array_chunk($permissions, $pageHeight);
		$maxPageNumber = count($chunkedPermissions);

		if(!isset($args[1]) || !is_numeric($args[1]) || $args[1] <= 0) {
			$pageNumber = 1;
		} elseif($args[1] > $maxPageNumber) {
			$pageNumber = $maxPageNumber;
		} else {
			$pageNumber = $args[1];
		}

		$perms = implode("\n" . TF::GREEN . " - ", $chunkedPermissions[$pageNumber - 1]);
		$sender->sendMessage(TF::GREEN . "List of all rank permissions for {$rank->getName()} ($pageNumber / $maxPageNumber) :\n" . TF::GREEN . " - $perms");
	}

}