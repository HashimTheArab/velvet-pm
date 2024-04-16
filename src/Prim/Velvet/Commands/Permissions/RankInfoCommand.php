<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function is_null;
use function implode;
use function count;

class RankInfoCommand extends Command {

	public function __construct(){
		parent::__construct(
			'rankinfo',
			TF::LIGHT_PURPLE . 'Shows information about a specific rank! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/rankinfo <rank>'
		);

		$this->setPermission('velvet.ranks.view');
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

        $rank = PermissionManager::getInstance()->getRank($args[0]);
        if(is_null($rank)) {
            $sender->sendMessage(Translator::RANK_DOESNT_EXIST);
            return;
        }

        $parents = [];
        foreach($rank->getParentRanks() as $tempRank) $parents[] = $tempRank->getName();

        $sender->sendMessage(
        	TF::GREEN . "-- Rank Information for {$rank->getName()} --\n" .
			'Alias: ' . $rank->getAlias() . "\n" .
			'Default Rank: ' . $rank->isDefault() ? 'True' : 'False' . "\n" .
			'Parents: ' . (empty($parents) ? 'None' : implode(', ', $parents))
		);
    }

}