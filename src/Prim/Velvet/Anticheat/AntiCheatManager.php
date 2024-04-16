<?php

namespace Prim\Velvet\Anticheat;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use Prim\Velvet\Anticheat\Checks\Autoclicker\AutoclickerA;
use Prim\Velvet\Anticheat\Checks\Check;
use Prim\Velvet\Anticheat\Checks\Timer\TimerA;
use Prim\Velvet\Anticheat\Checks\Velocity\VelocityA;

class AntiCheatManager {

	const PUNISHMENT_TYPE_NONE = 0;
	const PUNISHMENT_TYPE_KICK = 1;
	const PUNISHMENT_TYPE_BAN = 2;

	public static self $instance;

	public int $checkAmount = 0;
	public array $checks = [];

	public function __construct(){
		self::$instance = $this;
		$this->checks = [
			new AutoClickerA('Autoclicker', 'A', 'Checks for high cps', self::PUNISHMENT_TYPE_NONE, 20),
			new VelocityA('Velocity', 'A', 'Checks for abnormal vertical knockback', self::PUNISHMENT_TYPE_BAN, 20),
			new TimerA('Timer', 'A', 'Checks for movement packets sending faster than they should be', self::PUNISHMENT_TYPE_BAN, 15)
		];
	}

	/**
	 * @param ServerboundPacket|ClientboundPacket $packet
	 * @return array<int, Check>|null
	 */
	public function getChecks(ServerboundPacket|ClientboundPacket $packet) : ?array {
		return match($packet::NETWORK_ID){
			InventoryTransactionPacket::NETWORK_ID, LevelSoundEventPacket::NETWORK_ID => [$this->checks[0]],
			PlayerAuthInputPacket::NETWORK_ID => [$this->checks[1], $this->checks[2]],
			default => null
		};
	}

}