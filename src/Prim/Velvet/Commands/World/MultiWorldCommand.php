<?php

namespace Prim\Velvet\Commands\World;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Commands\BaseSubCommand;
use Prim\Velvet\Commands\World\SubCommands\WorldCreateSubCommand;
use Prim\Velvet\Commands\World\SubCommands\WorldDeleteSubCommand;
use Prim\Velvet\Commands\World\SubCommands\WorldInfoSubCommand;
use Prim\Velvet\Commands\World\SubCommands\WorldListSubcommand;
use Prim\Velvet\Commands\World\SubCommands\WorldLoadSubcommand;
use Prim\Velvet\Commands\World\SubCommands\WorldTeleportSubcommand;
use Prim\Velvet\Commands\World\SubCommands\WorldUnloadSubCommand;
use Prim\Velvet\Utils\Translator;
use function array_slice;
use function count;
use function strtolower;

class MultiWorldCommand extends BaseSubCommand {

	public function __construct(){
		parent::__construct(
			'multiworld',
			TF::LIGHT_PURPLE . 'World Management Commands ' . TF::GREEN . '(Staff!)',
			null,
			['mw']
		);
		$this->setPermission('velvet.multiworld');
	}

	public function registerSubCommands() : void {
		$this->subCommands['create'] = new WorldCreateSubCommand;
		$this->subCommands['teleport'] = new WorldTeleportSubcommand;
		$this->subCommands['list'] = new WorldListSubcommand;
		$this->subCommands['load'] = new WorldLoadSubcommand;
		$this->subCommands['unload'] = new WorldUnloadSubcommand;
		$this->subCommands['delete'] = new WorldDeleteSubcommand;
		$this->subCommands['info'] = new WorldInfoSubcommand;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1){
			$this->sendHelpMessage($sender);
			return;
		}

		if(strtolower($args[0]) === 'tp'){
			$this->subCommands['teleport']->executeSub($sender, array_slice($args, 1));
			return;
		}

		if(isset($this->subCommands[strtolower($args[0])])){
			$this->subCommands[strtolower($args[0])]->executeSub($sender, array_slice($args, 1));
			return;
		}
		$this->sendHelpMessage($sender);
	}

	public function sendHelpMessage(CommandSender $sender){
		$sender->sendMessage(
			TF::DARK_AQUA . "--World Management Help Page--\n" .
			TF::LIGHT_PURPLE . '/mw create ' . TF::GRAY . "Create a world\n" .
			TF::LIGHT_PURPLE . '/mw teleport ' . TF::GRAY . "Teleport to a world\n" .
			TF::LIGHT_PURPLE . '/mw list ' . TF::GRAY . "Displays a list of all worlds\n" .
			TF::LIGHT_PURPLE . '/mw <load:unload> ' . TF::GRAY . "Load or unload a world\n" .
			TF::LIGHT_PURPLE . '/mw delete ' . TF::GRAY . "Delete a world\n" .
			TF::LIGHT_PURPLE . '/mw info ' . TF::GRAY . "Displays info about a world\n" .
			TF::LIGHT_PURPLE . '/mw rename ' . TF::GRAY . "Rename a world\n"
		);
	}

}