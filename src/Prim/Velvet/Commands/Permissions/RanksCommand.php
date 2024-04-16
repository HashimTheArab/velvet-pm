<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function implode;

class RanksCommand extends Command {

	public function __construct(){
		parent::__construct(
			'ranks',
			TF::LIGHT_PURPLE . 'Lists all the ranks! ' . TF::GREEN . '(Staff!)',
		);

		$this->setPermission('velvet.ranks.view');
	}

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

        $result = [];
        foreach(PermissionManager::getInstance()->getRanks() as $rank) $result[] = $rank->getName();

        $sender->sendMessage(TF::GREEN . 'Ranks: ' . implode(", ", $result));
    }

}