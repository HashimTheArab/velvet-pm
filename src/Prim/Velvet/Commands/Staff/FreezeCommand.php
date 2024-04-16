<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function count;
use function date;
use function implode;
use function time;

class FreezeCommand extends Command{

    public function __construct(){
        parent::__construct(
            'freeze',
            TF::LIGHT_PURPLE . 'Freeze or thaw another player!' . Translator::COMMAND_STAFF,
            TF::RED . 'Usage: ' . TF::GRAY . '/freeze <player>'
        );
        $this->setPermission('velvet.freeze');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){

        if(!$sender->hasPermission($this->getPermission())){
            $sender->sendMessage(Translator::NO_PERMISSION);
            return;
        }

        if(count($args) < 1) {
        	$sender->sendMessage($this->usageMessage);
        	return;
		}

		$name = $args[0];
		$target = Server::getInstance()->getPlayerByPrefix($name);

		if(!$target instanceof VelvetPlayer || !$target->spawned){
			$sender->sendMessage(TF::YELLOW . $name . TF::RED . ' is not online!');
			return;
		}

		if($target->hasFlag(Flags::FROZEN)){
			$target->setFlag(Flags::FROZEN);
			$target->setImmobile(false);
			$target->sendMessage(TF::AQUA . "You have been unfrozen by " . $sender->getName() . '!');
			$sender->sendMessage(TF::AQUA . "You have unfrozen " . TF::YELLOW . $target->getName() . '!');
			$frozen = false;
		} else {
			$target->setFlag(Flags::FROZEN);
			$target->setImmobile();
			$target->sendMessage(TF::AQUA . 'You have been frozen by ' . $sender->getName() . '!');
			$sender->sendMessage(TF::AQUA . 'You have frozen ' . TF::YELLOW . $target->getName() . '!');
			$sender->getServer()->broadcastMessage(TF::YELLOW . $target->getName() . TF::AQUA . ' was frozen by ' . TF::YELLOW . $sender->getName() . '!');
			$frozen = true;
		}

		$webHook = new Webhook(Translator::COMMANDS_WEBHOOK);
		$msg = new Message();
		$embed = new Embed();
		$embed->setTitle('Command Ran! (/freeze)');
		$embed->setDescription("Mod: {$sender->getName()}\nCommand: /freeze " . implode(' ', $args) . "\nState: " . ($frozen ? 'Frozen' : 'Thawed'));
		$embed->setFooter(date('m/d/Y @ h:i:s a', time()));
		$msg->addEmbed($embed);
		$webHook->send($msg);
    }

}