<?php

namespace Prim\Velvet\Commands\World\SubCommands;

use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Prim\Velvet\Commands\SubCommand;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function count;

class WorldInfoSubCommand extends SubCommand {

	public function executeSub(CommandSender $sender, array $args) : void {
		if(!$sender instanceof Player){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		$world = $sender->getWorld();
		$sender->sendMessage(
			TF::GREEN . "--World Info--\n" . TF::LIGHT_PURPLE . 'Display Name: ' . TF::GRAY . $world->getDisplayName() . "\n" .
			TF::LIGHT_PURPLE . 'Folder Name: ' . TF::GRAY . $world->getFolderName() . "\n" .
			TF::LIGHT_PURPLE . 'Player Count: '  .TF::GRAY . count($world->getPlayers()) . "\n" .
			TF::LIGHT_PURPLE . 'Generator: ' . TF::GRAY . $world->getProvider()->getWorldData()->getGenerator() . "\n" .
			TF::LIGHT_PURPLE . 'Seed: ' . TF::GRAY . $world->getSeed() . "\n" .
			TF::LIGHT_PURPLE . 'Time: ' . TF::GRAY . $world->getTime()
		);
	}

}