<?php

namespace Prim\Velvet\Commands\Permissions;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Translator;
use function count;

class SetUPermCommand extends Command {

	public function __construct(){
		parent::__construct(
			'setuperm',
			TF::LIGHT_PURPLE . 'Add a permission to a specific player! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/setuperm <player> <permission>'
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
		PermissionManager::getInstance()->setPermission($player, $args[1]);
        $sender->sendMessage(TF::GREEN . 'Successfully added the permission ' . TF::YELLOW . $args[1] . TF::GREEN . 'to the player ' . TF::YELLOW . $player->getName() . '!');
    }

}