<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;

class SettingsCommand extends Command {

	public function __construct(){
		parent::__construct(
			'settings',
			TF::LIGHT_PURPLE . 'Access your player settings!',
		);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof VelvetPlayer){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}
		Main::getMain()->forms->settingsForm($sender);
	}

}