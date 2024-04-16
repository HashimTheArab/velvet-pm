<?php

namespace Prim\Velvet\Commands\World\SubCommands;

use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\world\WorldCreationOptions;
use Prim\Velvet\Commands\SubCommand;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\Worlds\VoidGenerator;
use pocketmine\utils\TextFormat as TF;
use function count;
use function strtolower;

class WorldCreateSubCommand extends SubCommand {

	public function __construct(){
		parent::__construct(TF::RED . 'Usage: ' . TF::GRAY . '/mw create <name> <generator: void>');
	}

	public function executeSub(CommandSender $sender, array $args) : void {
		if(count($args) < 2){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		if($sender->getServer()->getWorldManager()->isWorldGenerated($args[0])){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::RED . 'A world with this name already exists!');
			return;
		}

		$sender->getServer()->getWorldManager()->generateWorld($args[0], WorldCreationOptions::create()->setSpawnPosition(new Vector3(0, 64, 0))->setGeneratorClass($this->getGenerator($args[1])));
		$sender->sendMessage(
			TF::LIGHT_PURPLE . "A world has been generated with the following options:\n" .
			TF::LIGHT_PURPLE . 'Name: ' . TF::GRAY . "$args[0]\n" . TF::LIGHT_PURPLE . 'Generator: ' . TF::GRAY . $args[1]
		);
	}

	public function getGenerator(string $name) : string {
		return match(strtolower($name)){
			'void' => VoidGenerator::class,
			default => VoidGenerator::class
		};
	}

}