<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function array_slice;
use function implode;
use function count;

class KickCommand extends Command{

    public function __construct(){
        parent::__construct(
            'kick',
            TF::LIGHT_PURPLE . 'Kick another player from the server!' . Translator::COMMAND_STAFF,
            TF::RED . 'Usage: ' . TF::GRAY . "/kick <player> <reason>"
        );
        $this->setPermission('velvet.kick');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender->hasPermission($this->getPermission())){
            $sender->sendMessage(Translator::NO_PERMISSION);
            return;
        }

        if(count($args) < 2){
        	$sender->sendMessage($this->usageMessage);
        	return;
		}

		$reason = implode(' ', array_slice($args, 1));

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);

		if(!$target instanceof VelvetPlayer || !$target->spawned){
			$sender->sendMessage(TF::YELLOW . $args[0] . TF::RED . ' is not online!');
			return;
		}

		if(($target->hasFlag(Flags::STAFF) || $sender->getServer()->isOp($target->getName())) && $sender->getName() !== Translator::OWNER_NAME) {
			$sender->sendMessage(TF::DARK_RED . 'That user is a mod or op!');
			return;
		}

		$target->kick(
			TF::RED . 'You were kicked by ' . TF::YELLOW . $sender->getName() .
			TF::EOL . TF::RED . 'Reason: ' . TF::YELLOW . $reason, false
		);
		$sender->getServer()->broadcastMessage(
			TF::RED . $target->getName() . TF::YELLOW . ' was kicked by ' . TF::GREEN .
			$sender->getName() . TF::EOL . TF::RED . 'Reason: ' . TF::YELLOW . $reason
		);
    }

}