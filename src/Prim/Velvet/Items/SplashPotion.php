<?php

namespace Prim\Velvet\Items;

use pocketmine\entity\Location;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\PotionType;
use pocketmine\data\bedrock\PotionTypeIdMap;
use Prim\Velvet\Entities\SplashPotion as CustomPotion;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\SplashPotion as PMPotion;
use pocketmine\player\Player;

class SplashPotion extends PMPotion {

	public PotionType $potionType;

	public function __construct(){
		parent::__construct(new ItemIdentifier(ItemIds::SPLASH_POTION, PotionTypeIdMap::getInstance()->toId(PotionType::STRONG_HEALING())), 'Splash Potion', PotionType::STRONG_HEALING());
		$this->potionType = PotionType::STRONG_HEALING();
	}

	public function createEntity(Location $location, Player $thrower) : Throwable {
		return new CustomPotion($location, $thrower, $this->potionType);
	}

}