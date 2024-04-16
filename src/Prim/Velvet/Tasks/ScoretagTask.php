<?php

namespace Prim\Velvet\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use Prim\Velvet\Main;

class ScoretagTask extends Task {

	private Main $main;
	private Server $server;

	public function __construct(Main $main){
		$this->main = $main;
		$this->server = $this->main->getServer();
	}

	public function onRun() : void {
		foreach($this->server->getOnlinePlayers() as $player) $this->main->scoretag($player);
	}

}