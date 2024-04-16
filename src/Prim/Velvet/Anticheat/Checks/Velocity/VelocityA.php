<?php

namespace Prim\Velvet\Anticheat\Checks\Velocity;

use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use Prim\Velvet\Anticheat\Checks\Check;
use Prim\Velvet\Sessions\Session;
use function max;
use function round;

// skidded off ethaniccc thanks!
class VelocityA extends Check {

	public function run(ServerboundPacket|PlayerAuthInputPacket $packet, Session $session) : void {
		$motion = $session->acData->currentMotion ?? null;
		if($motion !== null){
			if(!$session->acData->fullySpawned || $session->owner->isImmobile()){
				$motion->y = 0;
				return;
			}
			$acData = $session->acData;
			if($motion->y > 0.005 && !$acData->hasBlockAbove){
				$velo = $acData->currentMoveDelta->y / $motion->y;
				if($velo < 0.9999 && $velo >= 0.0) {
					$this->flag($session, ['velo' => round($velo, 5)]);
				} else {
					$flagData = $acData->flags[$this->id] ?? null;
					if($flagData !== null){
						$flagData->flags = max($flagData->flags - 0.2, 0);
					}
				}
				$acData->currentMotion->y = ($acData->currentMotion->y - 0.08) * 0.98;
			} else {
				$acData->currentMotion = null;
			}
		}
	}

}