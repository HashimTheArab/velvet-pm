<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Tasks\BuildResetTask;
use Prim\Velvet\Utils\Translator;
use function microtime;

class ResetBuildCommand extends Command{

	public function __construct(){
		parent::__construct(
			'resetbuild',
			TF::LIGHT_PURPLE . 'Reset the BuildUHC arena!' . Translator::COMMAND_STAFF,
			null,
			['rb']
		);
		$this->setPermission('builduhc.reset');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		$sender->sendMessage(TF::GREEN . 'Resetting the arena!');
		$start = microtime(true);
		Main::getMain()->getScheduler()->scheduleTask(new BuildResetTask());
		$sender->sendMessage(TF::YELLOW . 'The arena has been reset in ' . TF::LIGHT_PURPLE . (microtime(true) - $start) . 's!');
	}

}