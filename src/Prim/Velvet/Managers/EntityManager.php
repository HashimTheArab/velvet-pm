<?php

namespace Prim\Velvet\Managers;

use Prim\Velvet\Entities\FloatingText\FloatingText;
use Prim\Velvet\Main;
use function file_get_contents;
use function json_decode;

class EntityManager {

	public static self $instance;

	public function __construct(public Main $main){
		self::$instance = $this;
	}

	public array|null $leaderboardInfo = [];
	public ?FloatingText $floatingText = null;

	public function getLeaderboards() : array {
		return json_decode(file_get_contents($this->main->getDataFolder() . 'leaderboards.json'), true);
	}

	public static function getInstance() : self {
		return self::$instance;
	}

}