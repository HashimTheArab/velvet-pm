<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function count;

class UnsetUPermCommand extends Command {

	public function __construct(){
		parent::__construct(
			'unsetuperm',
			TF::LIGHT_PURPLE . 'Remove a permission from a specific player! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/unsetuperm <player> <permission>'
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

        $player = PermissionManager::getInstance()->getPlayer($args[0]);
		PermissionManager::getInstance()->unsetPermission($player, $args[1]);
        
        $sender->sendMessage(TF::GREEN . 'Successfully removed the permission ' . TF::YELLOW . $args[1] . TF::GREEN . ' from the player ' . TF::YELLOW . $player->getName() . '!');
    }

}