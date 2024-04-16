<?php

namespace Prim\Velvet\Commands;

use pocketmine\command\CommandSender;

abstract class SubCommand {

	public string $usageMessage = '';

	public function __construct(string $usageMessage = ''){
		$this->usageMessage = $usageMessage;
	}

	abstract function executeSub(CommandSender $sender, array $args) : void;
}