<?php

namespace Prim\Velvet;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use Prim\Velvet\Sessions\SessionManager;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\world\sound\EntityLandSound;
use pocketmine\world\sound\EntityLongFallSound;
use pocketmine\world\sound\EntityShortFallSound;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Scoreboard;
use function array_filter;
use function array_pop;
use function array_unshift;
use function ceil;
use function count;
use function microtime;
use function round;

class VelvetPlayer extends Player {

	public int $scoreboard = Scoreboard::NORMAL;
	public int $flags = 0;

	public int $deviceOS;
	public int $inputMode;
	public string $deviceID;
	public string $deviceModel;

	public int $chatCooldown = 0;
	public int $gappleCooldown = 0;
	public int $pearlCooldown = 0;
	public array $clicks = [];

	public function setFlag(int $flag) : void {
		$this->flags ^= 1 << $flag;
	}

	public function hasFlag(int $flag) : bool {
		return (bool)($this->flags & (1 << $flag));
	}

	public function setScoreboardType(int $type) : void {
		$this->scoreboard = $type;
		Scoreboard::getInstance()->remove($this);
		Scoreboard::getInstance()->new($this);
		Scoreboard::getInstance()->sendScoreboard(SessionManager::getInstance()->getSession($this));
	}

	public function newScoreboard() : void {
		$this->scoreboard = Scoreboard::NORMAL;
		Scoreboard::getInstance()->new($this);
		Scoreboard::getInstance()->sendScoreboard(SessionManager::getInstance()->getSession($this));
	}

	public function calculateFallDamage(float $fallDistance): float{
		return 0;
	}

	public function teleport(Vector3 $pos, ?float $yaw = null, ?float $pitch = null) : bool {
		$t = parent::teleport($pos, $yaw, $pitch);
		$this->broadcastMotion();
		return $t;
	}

	public function canBeCollidedWith() : bool {
		return parent::canBeCollidedWith() && !$this->hasFlag(Flags::VANISHED);
	}

	public function addClick() : void {
		array_unshift($this->clicks, microtime(true));
		if(count($this->clicks) >= 50) array_pop($this->clicks);
	}

	public function getClicks() : float {
		$deltaTime = 1.0;
		if(empty($this->clicks)) return 0.0;
		$ct = microtime(true);
		return round(count(array_filter($this->clicks, function(float $t) use ($deltaTime, $ct) : bool {
			return ($ct - $t) <= $deltaTime;
		})) / $deltaTime, 1);
	}

}