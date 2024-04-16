<?php

namespace Prim\Velvet\Entities;

use pocketmine\color\Color;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\SplashPotion as PocketminePotion;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\VelvetPlayer;
use function count;
use function max;
use function min;
use function sqrt;

class SplashPotion extends PocketminePotion {

	protected $gravity = 0.06;
	protected $drag = 0.01; #Default is 0.01

	protected function onHit(ProjectileHitEvent $event) : void{
		$effects = $this->getPotionEffects();
		$hasEffects = true;

		if(count($effects) === 0){
			$particle = new PotionSplashParticle(PotionSplashParticle::DEFAULT_COLOR());
			$hasEffects = false;
		} else {
			$colors = [];
			$player = $this->getOwningEntity();
			if($player instanceof VelvetPlayer){
				$clr = SessionManager::getInstance()->getSession($player)->potColor;
			} else {
				$clr = [248, 36, 35];
			}
			$colors[] = new Color((int) $clr[0], (int) $clr[1], (int) $clr[2]);
			$particle = new PotionSplashParticle(Color::mix(...$colors));
		}

		$this->getWorld()->addParticle($this->location, $particle);
		$this->broadcastSound(new PotionSplashSound());

		if($hasEffects){
			if(!$this->willLinger()){
				foreach($this->getWorld()->getNearbyEntities($this->boundingBox->expandedCopy(3, 2.125, 3), $this) as $entity){
					if($entity instanceof Living and $entity->isAlive()){
						$distanceSquared = $entity->getEyePos()->distanceSquared($this->location);
						//$distanceSquared = $entity->add(0, $entity->getEyeHeight())->distanceSquared($this);
						if($distanceSquared > 16){ //4 blocks
							continue;
						}

						$distanceMultiplier = $entity->getId() === $this->ownerId ? 0.59 : max(min(1 - (sqrt($distanceSquared) / 3.9), 0.6), 0.48);
						if($event instanceof ProjectileHitEntityEvent and $entity->id === $event->getEntityHit()->id){
							$distanceMultiplier = 0.65; // hits the entity default 0.69
							$player = $this->getOwningEntity();
							if($player instanceof VelvetPlayer && $player->hasFlag(Flags::POTDING)){
								$p = $player->getPosition();
								$pk = new PlaySoundPacket();
								$pk->soundName = 'random.orb';
								$pk->pitch = 1.0;
								$pk->volume = 500.0;
								$pk->x = $p->x;
								$pk->y = $p->y;
								$pk->z = $p->z;
								$player->getNetworkSession()->sendDataPacket($pk);
							}
						}
						foreach($this->getPotionEffects() as $effect){
							$entity->heal(new EntityRegainHealthEvent($entity, (4 << $effect->getAmplifier()) * $distanceMultiplier * 1.75, EntityRegainHealthEvent::CAUSE_MAGIC));
						}
					}
				}
			}
		}
	}

}