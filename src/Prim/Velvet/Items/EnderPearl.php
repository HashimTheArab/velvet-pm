<?php

namespace Prim\Velvet\Items;

use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\EnderPearl as PocketminePearl;
use Prim\Velvet\Entities\EnderPearl as CustomPearl;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class EnderPearl extends PocketminePearl {

	public function __construct(){
		parent::__construct(new ItemIdentifier(ItemIds::ENDER_PEARL, 0), 'Ender Pearl');
	}

	public function getThrowForce() : float {
		return 1.9;
	}

	public function createEntity(Location $location, Player $thrower): Throwable{
		return new CustomPearl($location, $thrower);
	}

}