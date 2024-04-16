<?php

namespace Prim\Velvet\Commands;

use pocketmine\command\Command;

abstract class BaseSubCommand extends Command {

	/** @var array<string, SubCommand> */
	public array $subCommands = [];

	public function __construct(string $name, string $description = "", ?string $usageMessage = null, array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->registerSubCommands();
	}

	abstract function registerSubCommands() : void;
}