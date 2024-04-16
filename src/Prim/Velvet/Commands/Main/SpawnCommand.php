<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;

class SpawnCommand extends Command {

	public function __construct(public Main $main){
		parent::__construct(
			'spawn',
			TF::LIGHT_PURPLE . 'Teleports you back to spawn!'
		);
		$this->setAliases(['hub']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) {
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		$session = $this->main->sessionManager->getSession($sender);
		if($session->hasMatch()){
			$sender->sendMessage(TF::RED . "You cannot use this command while in a duel!");
			return;
		}

		if($this->main->game !== null && $this->main->game->isFighting($sender)){
			$sender->sendMessage(TF::RED . 'You cannot use this command while in an event!');
			return;
		}

		$sender->sendActionBarMessage(TF::LIGHT_PURPLE . 'Welcome to spawn!');
		$sender->teleport($sender->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
	}

}