<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;

class SocialSpyCommand extends Command{

	public function __construct(){
		parent::__construct(
			'socialspy',
			TF::LIGHT_PURPLE . 'View private messages from other players!' . Translator::COMMAND_STAFF,
		);
		$this->setPermission('velvet.socialspy');
		$this->setAliases(['ss']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof VelvetPlayer){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		$sender->sendMessage(($sender->hasFlag(Flags::SOCIALSPY) ? TF::RED : TF::GREEN) . 'You have ' . ($sender->hasFlag(Flags::SOCIALSPY) ? 'disabled' : 'enabled') . ' social spy!');
		$sender->setFlag(Flags::SOCIALSPY);
	}

}