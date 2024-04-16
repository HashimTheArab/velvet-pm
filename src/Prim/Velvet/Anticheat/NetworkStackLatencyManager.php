<?php

namespace Prim\Velvet\Anticheat;

use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use Prim\Velvet\Sessions\Session;
use function mt_rand;

// skidded off ethaniccc thanks!
class NetworkStackLatencyManager {

	public static function send(Session $session, callable $onReceive) : void {
		$pk = new NetworkStackLatencyPacket;
		$pk->needResponse = true;
		$pk->timestamp = mt_rand(1, 100000000) * 1000;
		$timestamp = $pk->timestamp;
		if($session->owner->deviceOS === DeviceOS::PLAYSTATION){
			$timestamp /= 1000;
		}
		$session->acData->latencyTimestamps[$timestamp] = $onReceive;
		$session->owner->getNetworkSession()->sendDataPacket($pk);
	}

	public static function execute(Session $session, int $timestamp): void {
		$callable = $session->acData->latencyTimestamps[$timestamp] ?? null;
		if ($callable !== null) {
			$callable($timestamp);
			unset($session->acData->latencyTimestamps[$timestamp]);
		}
	}

}