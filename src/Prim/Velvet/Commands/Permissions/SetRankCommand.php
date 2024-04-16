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

class SetRankCommand extends Command {

	public function __construct(){
		parent::__construct(
			'setrank',
			TF::LIGHT_PURPLE . 'Set a players rank! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/setrank <player> <rank>'
		);

		$this->setPermission('velvet.ranks.manage');
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

        $player = PermissionManager::getInstance()->getPlayer($args[0]);
        $rank = PermissionManager::getInstance()->getRank($args[1]);
        
        if(is_null($rank)){
            $sender->sendMessage(Translator::RANK_DOESNT_EXIST);
            return;
        }

        PermissionManager::getInstance()->setRank($player, $rank);
        $sender->sendMessage(TF::GREEN . "You have set {$player->getName()}'s rank to {$rank->getName()}!");
        
        if($player instanceof Player) {
        	$player->sendMessage(TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' has set your rank to ' . TF::LIGHT_PURPLE . $rank->getName() . '!');
        }
    }

}