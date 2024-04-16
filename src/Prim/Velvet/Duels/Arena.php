<?php

namespace Prim\Velvet\Duels;

use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class Arena {

	protected Position $spawn1;
	protected Position $spawn2;
	protected World $level;
	protected string $name;
	protected string $mode;
	protected int $status = Translator::FREE;

	public function __construct(string $name, string $mode, Vector3 $spawn1, Vector3 $spawn2, World $level){
		$this->name = $name;
		$this->mode = $mode;
		$this->level = $level;
		$this->spawn1 = new Position($spawn1->x, $spawn1->y, $spawn1->z, $this->level);
		$this->spawn2 = new Position($spawn2->x, $spawn2->y, $spawn2->z, $this->level);
	}

	public function getSpawn1() : Position {
		return $this->spawn1;
	}

	public function getSpawn2() : Position {
		return $this->spawn2;
	}

	public function getName() : string {
		return $this->name;
	}

	public function getMode() : string {
		return $this->mode;
	}

	public function getLevel() : World {
		return $this->level;
	}

	public function getStatus() : int {
		return $this->status;
	}

	public function setStatus(int $status){
		$this->status = $status;
	}

}