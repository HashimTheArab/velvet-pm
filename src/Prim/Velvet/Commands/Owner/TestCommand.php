<?php

namespace Prim\Velvet\Commands\Owner;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use function count;
use function implode;

class TestCommand extends Command {

	public function __construct(){
		parent::__construct(
			"mtest",
			TF::AQUA . 'View details about all ongoing duels.',
		);
		$this->setPermission('test.command');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		$b = Main::getMain()->matchManager->matches;

		$ids = [];
		$types = [];
		$status = [];
		$countdown = [];
		$winners = [];
		$arenas = [];
		foreach($b as $id => $match){
			$ids[] = $id;
			$types[] = $match->type;
			$status[] = $match->status;
			$countdown[] = $match->countdown;
			$winners[] = $match->getWinner() !== null ? $match->getWinner()->getName() : "None";
			$arenas[] = $match->getArena()->getName();
		}

		$sender->sendMessage(
			TF::GREEN . "Match Info:\n" . "Ids: " . TF::WHITE . implode(", ", $ids) . "\n" . TF::GREEN .
			"Types: " . TF::WHITE . implode(", ", $types) . TF::GREEN . "\nStatus's: " . TF::WHITE . implode(", ", $status) . "\n" .
			TF::GREEN . "Countdowns: " . TF::WHITE . implode(", ", $countdown) . "\n" . TF::GREEN . "Winners: " . TF::WHITE . implode(", ", $winners) . "\n" .
			TF::GREEN . "Arenas: " . TF::WHITE . implode(", ", $arenas)
		);
		$sender->sendMessage("There are " . TF::AQUA . count($b) . TF::WHITE . " matches.");
	}

}