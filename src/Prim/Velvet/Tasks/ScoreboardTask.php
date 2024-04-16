<?php

namespace Prim\Velvet\Tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use Prim\Velvet\Sessions\Session;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Scoreboard;

class ScoreboardTask extends Task {

	private Player $player;
	private ?Session $session;
	private Main $main;

	public function __construct(Main $main, Player $player){
		$this->main = $main;
		$this->player = $player;
		$this->session = $main->sessionManager->getSession($player);
	}

	public function onRun() : void {
		if(!$this->player->isOnline()) {
			$this->getHandler()->cancel();
			unset($this->main->scoreboards[$this->player->getName()]);
			return;
		}
		Scoreboard::getInstance()->sendScoreboard($this->session);
	}
}