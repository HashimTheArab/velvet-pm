<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Tasks\ScoreboardTask;
use Prim\Velvet\Utils\Scoreboard;

class ScoreboardCommand extends Command{

	private Main $main;

	public function __construct(Main $main){
		parent::__construct(
			'scoreboard',
			TF::LIGHT_PURPLE . 'Toggle on or off your scoreboard!.'
		);
		$this->main = $main;
		$this->setAliases(['sb']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;

		$name = $sender->getName();

		if(!isset($this->main->scoreboards[$name])){
			Scoreboard::getInstance()->new($sender);
			$task = $this->main->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this->main, $sender), 60);
			$sender->sendMessage(TF::GREEN . 'Your scoreboard has been turned on!');
			$this->main->scoreboards[$name] = $task;
		} else {
			$this->main->scoreboards[$name]->cancel();
			unset($this->main->scoreboards[$name]);
			Scoreboard::getInstance()->remove($sender);
			$sender->sendMessage(TF::RED . 'Your scoreboard has been turned off.');
		}
	}
}