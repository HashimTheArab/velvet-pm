<?php

namespace Prim\Velvet\Entities;

use pocketmine\entity\projectile\EnderPearl as PocketminePearl;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\Vector3;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;

class EnderPearl extends PocketminePearl {

	protected $gravity = 0.1;
	#protected $drag = -0.025;

	protected function onHit(ProjectileHitEvent $event) : void {
		$owner = $this->getOwningEntity();
		if($owner !== null){
			$this->getWorld()->addParticle($origin = $owner->getPosition(), new EndermanTeleportParticle());
			$this->getWorld()->addSound($origin, new EndermanTeleportSound());
			$owner->teleport($target = $event->getRayTraceResult()->getHitVector());
			$this->getWorld()->addSound($target, new EndermanTeleportSound());
		}
	}

	public function setMotion(Vector3 $motion) : bool{
		if(!$this->justCreated){
			$ev = new EntityMotionEvent($this, $motion->multiply(1.5));
			$ev->call();
			if($ev->isCancelled()) return false;
		}

		$this->motion = clone $motion->multiply(1.5);

		if(!$this->justCreated){
			$this->updateMovement();
		}

		return true;
	}

}