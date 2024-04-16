<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;

class SetGPermCommand extends Command {

	public function __construct(){
		parent::__construct(
			'setgperm',
			TF::LIGHT_PURPLE . 'Add a permission to a specific rank! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/setgperm <rank> <permission>'
		);

		$this->setPermission('velvet.ranks.edit');
	}

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$sender->hasPermission($this->getPermission())) {
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}
        
        if(count($args) < 2) {
            $sender->sendMessage($this->usageMessage);
            return;
        }

        $rank = PermissionManager::getInstance()->getRank($args[0]);
        if(is_null($rank)) {
            $sender->sendMessage(Translator::RANK_DOESNT_EXIST);
            return;
        }

		$rank->addPermission($args[1]);
        $sender->sendMessage(TF::GREEN . "The permission $args[1] has been added to the rank {$rank->getName()}!");
    }

}