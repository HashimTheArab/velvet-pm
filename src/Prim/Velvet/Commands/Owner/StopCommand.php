<?php

namespace Prim\Velvet\Commands\Owner;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;

class StopCommand extends Command {

	public function __construct(){
		parent::__construct(
			'stop',
			TF::LIGHT_PURPLE . 'Shut down the server! ' . TF::GREEN . '(Staff!)'
		);
		$this->setPermission('velvet.command.stop');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if($sender instanceof Player && $sender->getXuid() !== Translator::OWNER_XUID){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		foreach($sender->getServer()->getOnlinePlayers() as $p) $p->kick(Translator::SERVER_CLOSED, false);
		Server::getInstance()->shutdown();
	}

}