<?php

namespace Prim\Velvet\Anticheat;

use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\player\Player;
use function abs;
use function is_null;

class AntiCheatData {

	public float $timerLastTimestamp = -1;
	public float $timerBalance = 0.0;

	public array $latencyTimestamps = [];
	/** @var FlagData[] */
	public array $flags = [];

	public ?Vector3 $currentMotion = null;
	public ?Vector3 $currentLocation = null;
	public ?Vector3 $lastLocation = null;
	public ?Vector3 $lastClientPrediction = null;
	public ?Vector3 $currentMoveDelta = null;

	public bool $hasBlockAbove = false;
	public bool $fullySpawned = true;

	public function __construct(){
		$this->lastClientPrediction = new Vector3(0, -0.0784, 0);
	}

	public function handlePacketData(Player $player, PlayerAuthInputPacket $pk) : void {
		$this->lastClientPrediction = $pk->getDelta();
		$this->lastLocation = $this->currentLocation;
		$this->currentLocation = $pk->getPosition()->subtract(0, 1.62, 0);
		if(is_null($this->lastLocation)){
			$this->lastLocation = $this->currentLocation;
			$this->currentMotion = null;
		}
		$this->currentMoveDelta = $this->currentLocation->subtractVector($this->lastLocation);
		$this->hasBlockAbove = $this->lastClientPrediction->y > 0.005 && abs($this->lastClientPrediction->y - $this->currentMoveDelta->y) > 0.001 && $player->getWorld()->getBlock($this->currentLocation->add(0, 2, 0))->getId() !== BlockLegacyIds::AIR;
	}

}