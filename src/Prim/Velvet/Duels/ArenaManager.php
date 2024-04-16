<?php

namespace Prim\Velvet\Duels;

use pocketmine\math\Vector3;
use pocketmine\world\World;
use function array_rand;

class ArenaManager {

	private array $arenas = [];

	public function getArenaByMode(string $mode) : ?Arena {
		$arenalist = [];
		foreach($this->arenas as $arena){
			if($arena->getMode() === $mode && $arena->getStatus() !== Translator::BUSY){
				$arenalist[] = $arena;
			}
		}
		if(!empty($arenalist)) return $arenalist[array_rand($arenalist)];
		return null;
	}

	public function getArenaByName(string $name) : ?Arena {
		foreach($this->arenas as $arena){
			if($arena->getName() === $name) return $arena;
		}
		return null;
	}

	public function registerArena(string $name, string $mode, Vector3 $spawn1, Vector3 $spawn2, World $level) : void {
		$this->arenas[$name] = new Arena($name, $mode, $spawn1, $spawn2, $level);
	}

}