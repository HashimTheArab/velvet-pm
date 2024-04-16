<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;

class EmoteCommand extends Command {

	public function __construct(){
		parent::__construct(
			'emote',
			TF::LIGHT_PURPLE . 'Use any bedrock emote!'
		);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		return;
		if($sender instanceof Player) Main::getMain()->forms->emoteForm($sender);
	}
}