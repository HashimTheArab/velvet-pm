<?php

namespace Prim\Velvet\Anticheat\Checks\Timer;

use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use Prim\Velvet\Anticheat\Checks\Check;
use Prim\Velvet\Sessions\Session;
use function microtime;
use function round;

class TimerA extends Check {

	public function run(ServerboundPacket|PlayerAuthInputPacket $packet, Session $session) : void {
		$acData = $session->acData;
		if(!$session->owner->isAlive()){
			$acData->timerLastTimestamp = -1;
			return;
		}

		$timestamp = microtime(true);
		if($acData->timerLastTimestamp === -1.0){
			$acData->timerLastTimestamp = $timestamp;
			return;
		}

		$diff = $timestamp - $acData->timerLastTimestamp;
		$acData->timerBalance += 0.05;
		$acData->timerBalance -= $diff;
		if($acData->timerBalance >= 0.25){
			$this->flag($session, ['timer' => round($diff, 3)]);
			$acData->timerBalance = 0;
		}

		$acData->timerLastTimestamp = $timestamp;
	}

}