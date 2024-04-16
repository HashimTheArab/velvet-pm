<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\VelvetPlayer;
use function array_slice;
use function implode;
use function count;
use function is_null;

class TellCommand extends Command{

	public function __construct(){
		parent::__construct(
			'msg',
			TF::LIGHT_PURPLE . 'Send a private message to another!',
			TF::RED . 'Usage: ' . TF::GRAY . '/w <name> <message>'
		);
		$this->setAliases(['w', 'tell']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 2){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $args[0];
		$m = implode(' ', array_slice($args, 1));
		$target = Server::getInstance()->getPlayerByPrefix($name);

		/** @var VelvetPlayer $p */
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			if($p->hasFlag(Flags::SOCIALSPY)) $p->sendMessage(TF::YELLOW . $sender->getName() . ": " . TF::GRAY . TF::ITALIC . "/tell $name $m");
		}

		if(is_null($target)){
			$sender->sendMessage(TF::RED . "$name is not online.");
			return;
		}

		if($sender->getName() === $target->getName()){
			$sender->sendMessage(TF::RED . 'Why would you want to message yourself?');
			return;
		}

		$sender->sendMessage(TF::DARK_PURPLE . '[' . TF::LIGHT_PURPLE . 'You' . TF::GRAY . ' -> ' . TF::LIGHT_PURPLE . $target->getName() . TF::DARK_PURPLE . '] ' . TF::LIGHT_PURPLE . $m);
		$target->sendMessage(TF::DARK_PURPLE . '[' . TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' -> ' . TF::LIGHT_PURPLE . 'You' . TF::DARK_PURPLE . '] ' . TF::LIGHT_PURPLE . $m);
	}
}