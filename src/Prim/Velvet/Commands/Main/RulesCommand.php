<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;

class RulesCommand extends Command{

	public function __construct(){
		parent::__construct(
			'rules',
			TF::LIGHT_PURPLE . 'Make sure to read the rules to stay out of trouble!'
		);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$sender->sendMessage(TF::AQUA . "Rules:\n" .
			" - Do not Drag click, Bawl Click, or use any method that offers an unfair/unusual amount of cps.\n" .
			" - Do not use trigger stoppers or multiple input methods to click, these go with the above rule but may not be obvious.\n" .
			" - The minimum debounce allowed is 10ms.\n" .
			" - Do not use invisible or 4D skins. Cosmetics are allowed\n" .
			" - Do not interfere in fights or 2v1 / team on other players.\n" .
			' - There are more rules, a lot of them are common sense but you can read them at ' . Translator::DISCORD_LINK . '.'
		);
	}
}