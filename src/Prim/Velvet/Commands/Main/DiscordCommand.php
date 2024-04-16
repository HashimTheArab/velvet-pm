<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;

class DiscordCommand extends Command{

	public function __construct(){
		parent::__construct(
			'discord',
			TF::LIGHT_PURPLE . 'Sends you the Velvet discord link!'
		);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$sender->sendMessage(TF::GRAY . 'Join in on the fun at ' . TF::LIGHT_PURPLE . Translator::DISCORD_LINK . '!');
	}

}