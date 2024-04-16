<?php

namespace Prim\Velvet\Tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Enchants;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;

class BleedTask extends Task {

	public Main $main;
	public Enchants $enchants;

	public VelvetPlayer $player;
	public int $runs = 7;

	public function __construct(Main $main, VelvetPlayer $player){
		$this->main = $main;
		$this->player = $player;

		$this->enchants = $this->main->enchants;
	}

	public function onRun() : void {
		--$this->runs;
		if($this->runs <= 0 || !$this->player->isAlive() || !$this->player->isOnline() || $this->player->getWorld()->getFolderName() !== Translator::GOD_WORLD){
			if($this->player->hasFlag(Flags::BLEEDING)){
				$this->player->setFlag(Flags::BLEEDING);
			}
			$this->getHandler()->cancel();
			return;
		}
		$this->enchants->bleed($this->player);
	}
}