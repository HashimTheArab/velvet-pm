<?php

namespace Prim\Velvet\Bots;

use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use function is_null;

class PM3Bot extends Human {

	public float $blocksRunToPot = 3;
	public float $reach = 2.3;
	public float $speed = 0.35;
	public int $damage = 1;

	public int $knockbackTicks = 0;
	public int $inAirTicks = 0;
	public int $potTicks = 0;
	public int $pearlCooldown = 0;
	public int $swings = 0;

	public bool $isRunningAway = false;
	public Vector3 $potStartSpot;

	public Player $target;
	public string $difficulty;
	public int $pots = 34;
	public int $pearls = 16;

	public $jumpVelocity = 0.334;
	public $gravity = 0.0645;

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);

		$this->getArmorInventory()->setContents([Item::get(310), Item::get(311), Item::get(312), Item::get(313)]);
		$this->getInventory()->setItem(0, VanillaItems::DIAMOND_SWORD());
		$this->getInventory()->setItem(1, ItemFactory::getInstance()->get(438, 22));
		$this->getInventory()->setItem(2, Item::get(368));
		$this->getInventory()->setHeldItemIndex(0);

		$this->setCanSaveWithChunk(false);
	}

	public function getPots() : int {
		return $this->pots;
	}

	public function entityBaseTick(int $tickDiff = 1): bool{
		if($this->closed || is_null($this) || is_null($this->target) || !$this->target->isAlive()){
			return false;
		}

		$hasUpdate = parent::entityBaseTick($tickDiff);

		if(!$this->isAlive() && $this->target->isAlive()){
			$session = Main::getMain()->getSessionManager()->getSession($this->target);
			$match = $session->getMatch();
			if($match != null && $match->type === BotMatch::TYPE){
				$match->removePlayer($this->target, BotMatch::TYPE);
			}
			return false;
		}

		$this->setNameTag(TF::GOLD . "Rodger 2.0 " . TF::GREEN . "[" . round($this->getHealth(), 2) . "/" . $this->getMaxHealth() . "]");

		$pos = $this->target;
		$x = $pos->x - $this->x; //calculate position difference
		$z = $pos->z - $this->z; //calculate position difference

		if($x != 0 || $z != 0){ //if it is 0 that means the player and bot are at the same position
			if($this->knockbackTicks > 0){
				$this->knockbackTicks--;
				/*
				$base = 0.27;
				$baseY = 0.17; // or 2 and 5 ticks
				$xx = 0.5; //need to find the default x and z kb values
				$zz = 0.5;

				$this->motion->x += $xx * $f * $base;
				$this->motion->y += $baseY * (1 + ($baseY ** 2));
				$this->motion->z += $zz * $f * $base;
				*/

				$base = 0.177;
				$baseY = $this->y > $this->target->y + 1.2 ? 0.165 : 0.17;

				//$base = 0.045;
				//$baseY = 0.165;

				$deltaX = $this->x - $this->target->x;
				$deltaZ = $this->z - $this->target->z;
				$f = sqrt($deltaX ** 2 + $deltaZ ** 2);
				if ($f <= 0) return false;

				if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
					$f = 1 / $f;
					$this->motion->x /= 2;
					//$this->motion->y /= 2;
					$this->motion->z /= 2;

					$this->motion->x += $deltaX * $f * $base;
					$this->motion->y = $baseY;
					$this->motion->z += $deltaZ * $f * $base;

					if($this->isRunningAway || ($this->isPotting() && $this->getDirectionVector() !== $this->target->getDirectionVector())){
						$this->motion->x = -$this->motion->x;
						$this->motion->z = -$this->motion->z;
					}

				}

			} else {
				/*
				 * if($this->onGround && $this->distance($this->target) < 7){
					$x = $x + (lcg_value() * 10 - 1);
					$z = $z + (lcg_value() * 10 - 1);
					$xz_modulus = sqrt($x ** 2 + $z ** 2);
					if($xz_modulus > 0.0){
						$this->motion->x = ($this->speed * 0.35) * ($x / $xz_modulus);
						$this->motion->z = ($this->speed * 0.35) * ($z / $xz_modulus);
					}
				} else {
					$this->motion->x = $this->speed * 0.35 * ($x / (abs($x) + abs($z)));
					$this->motion->z = $this->speed * 0.35 * ($z / (abs($x) + abs($z)));
				}
				 */
				$this->motion->x = $this->speed * 0.35 * ($x / (abs($x) + abs($z)));
				$this->motion->z = $this->speed * 0.35 * ($z / (abs($x) + abs($z)));
				if($this->distance($this->target) < 1.3){
					$this->motion->x = -$this->motion->x;
					$this->motion->z = -$this->motion->z;
				}
			}
		}

		if($this->pearlCooldown > 0) $this->pearlCooldown--;
		$distance = $this->distance($this->target);
		if($distance > 20 && $this->pearlCooldown <= 0){
			if($this->pearls > 0){
				$this->pearlCooldown = 200;
				$this->lookAt($this->target);
				$this->calculatePearlPitch();
				$this->pearl();
			}
		}
		if($distance < 7){
			if($this->pearlCooldown <= 0 && mt_rand(1, 950) == 1 && $this->pearls > 0){
				$this->pearlCooldown = 200;
				$this->lookAt($this->target);
				$this->calculatePearlPitch();
				$this->pearl();
			}
		}

		if($this->y > $this->target->y + 1){
			$this->inAirTicks++;
		} else {
			$this->inAirTicks = 0;
		}


		$hp = [10, 7, 5];
		if($this->getHealth() <= $hp[array_rand($hp)] && mt_rand(0, 20) > 18){
			if($this->pots > 0 && !$this->isPotting()){
				$this->potTicks = 100;
				$this->potStartSpot = $this->getPosition();
			}
		}

		if($this->isPotting()){
			$this->potTicks--;
			$this->runAway();
			if($this->distance($this->potStartSpot) >= $this->blocksRunToPot && !$this->isLookingAt($this->target)){
				if($this->y > $this->target->y){
					$this->pitch = -25;
					$this->runAway();
					mt_rand(1, 4) === 1 ? $this->pot() : $this->instantPot();
				} else {
					$this->yaw = $this->yaw < 0 ? abs($this->yaw) : ($this->yaw == 0 ? -180 : -$this->yaw);
					$this->pitch = 85;
					$this->pot();
				}
				$this->potTicks = 0;
			}
		}

		if($distance > 7){
			if($distance > 10) $this->jump();
			$this->speed = 0.6;
		} else {
			$this->speed = 0.35;
		}

		if($this->inAirTicks > 55 && !$this->isRunningAway) $this->isRunningAway = true;
		if($this->onGround && $this->isRunningAway) $this->isRunningAway = false;

		if(mt_rand(0, 700) === 1) $this->swings = 0;

		if(!$this->isRunningAway && !$this->isPotting()){
			$this->lookAt($this->target);
			$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		}

		if($this->isRunningAway){
			$this->runAway();
			if($this->distance($this->target) >= mt_rand(8, 12)) $this->isRunningAway = false;
		} else {
			if($this->speed != 0.35) $this->speed = 0.35;
		}

		if($this->distance($this->target) <= mt_rand(5.7, 7) && $this->isLookingAt($this->target)){
			$this->getInventory()->setHeldItemIndex(0);
			if($this->swings < 80){
				$this->broadcastEntityEvent(4);
				$event = new EntityDamageByEntityEvent($this, $this->target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getInventory()->getItemInHand() instanceof Sword ? $this->damage + 7 : 0.5);
				$this->swings++;
				if($this->distance($this->target) <= $this->reach && $this->getInventory()->getItemInHand() instanceof Sword){
					$this->target->attack($event);
					$this->swings = 0;
				}
			} else {
				if($this->distance($this->target) <= $this->reach && $this->getInventory()->getItemInHand() instanceof Sword){
					$this->broadcastEntityEvent(4);
					$event = new EntityDamageByEntityEvent($this, $this->target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getInventory()->getItemInHand() instanceof Sword ? $this->damage + 7 : 0.5);
					$this->target->attack($event);
					$this->swings = 0;
				}
			}

		}
		return $hasUpdate;
	}

	public function knockBack(float $x, float $z, float $base = 0.4): void{
		$this->knockbackTicks = 5;
	}

	public function pot(){
		$this->getInventory()->setHeldItemIndex(1);
		$item = $this->getInventory()->getItemInHand();

		$nbt = Entity::createBaseNBT($this->add(0, $this->getEyeHeight()), $this->getDirectionVector(), $this->yaw, $this->pitch);
		if ($item->getId() === 438) $nbt->setShort("PotionId", 22);
		$projectile = Entity::createEntity($item->getProjectileEntityType(), $this->getLevel(), $nbt, $this);

		if($projectile !== null) {
			$projectile->setMotion($projectile->getMotion()->multiply($item->getThrowForce()));
			$projectile->spawnToAll();
			$this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
			$this->setHealth($this->getHealth() + 5);
			$this->pots--;
		}
	}

	public function instantPot(){
		$this->getInventory()->setHeldItemIndex(1);

		$this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_GLASS);
		$this->getLevel()->broadcastLevelEvent($this, LevelEventPacket::EVENT_PARTICLE_SPLASH, 4294452259);

		$this->setHealth($this->getHealth() + 10);
		$this->pots--;
	}

	public function pearl(){
		$this->getInventory()->setHeldItemIndex(2);
		$item = $this->getInventory()->getItemInHand();

		$nbt = Entity::createBaseNBT($this->add(0, $this->getEyeHeight()), $this->getDirectionVector(), $this->yaw, $this->pitch);
		$projectile = Entity::createEntity($item->getProjectileEntityType(), $this->getLevel(), $nbt, $this);

		if($projectile !== null) {
			$projectile->setMotion($projectile->getMotion()->multiply($item->getThrowForce()));
			$projectile->spawnToAll();
			$this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
		}
	}

	public function calculatePearlPitch() {
		$pitch = 1 - sqrt($this->distanceSquared($this->target));
		if($pitch <= -40) $pitch /= 1.3;
		if($pitch <= -55) $pitch /= 1.7;
		$this->pitch = $pitch;
	}

	//credit to ethaniccc
	public function isLookingAt(Vector3 $target) : bool{
		$horizontal = sqrt(($target->x - $this->x) ** 2 + ($target->z - $this->z) ** 2);
		$vertical = $target->y - $this->y;
		$expectedPitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

		$xDist = $target->x - $this->x;
		$zDist = $target->z - $this->z;
		$expectedYaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($expectedYaw < 0){
			$expectedYaw += 360.0;
		}

		return abs($expectedPitch - $this->getPitch()) <= 5 && abs($expectedYaw - $this->getYaw()) <= 10;
	}

	public function saveNBT(): void{
		$this->namedtag->setString("pvpreach", $this->reach);
		$this->namedtag->setString("blocksruntopot", $this->blocksRunToPot);
		$this->namedtag->setString("botdamage", $this->damage);
		$this->namedtag->setString("difficulty", $this->difficulty);
		parent::saveNBT();
	}

	public function initEntity(): void{
		parent::initEntity();
		$this->reach = $this->namedtag->getString("pvpreach");
		$this->blocksRunToPot = $this->namedtag->getString("blocksruntopot");
		$this->damage = $this->namedtag->getString("botdamage");
		$this->difficulty = $this->namedtag->getString("difficulty");
	}

	public function isPotting() : bool {
		return $this->potTicks > 0;
	}

	public function debug(string $message){
		$this->target->sendMessage(TF::BOLD . TF::GRAY . "[" . TF::RED . "DEBUG" . TF::GRAY . "]" . TF::RESET . TF::GRAY . " $message");
	}

	public function runAway(){
		$pitch = $this->pitch <= -25 || $this->pitch >= 25 ? 0 : $this->pitch;
		if($this->isLookingAt($this->target)) $this->setRotation($this->yaw + 180, $pitch);
		$this->motion->x = -$this->motion->x;
		$this->motion->z = -$this->motion->z;
		$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		$this->speed = 0.6;
	}

}