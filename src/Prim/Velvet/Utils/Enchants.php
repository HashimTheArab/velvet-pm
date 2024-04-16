<?php

namespace Prim\Velvet\Utils;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\player\Player;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\ExplodeSound;
use Prim\Velvet\Main;

class Enchants {

	public function __construct(public Main $main){}

	public function lightning(Player $player){
		$id = Entity::nextRuntimeId();
		$pk = AddActorPacket::create($id, $id, 'minecraft:lightning_bolt', $player->getPosition(), null, 0, 0, 0, [], [], []);
		$this->main->getServer()->broadcastPackets($player->getViewers(), [$pk]);
		$player->setOnFire(3);
		$player->setHealth($player->getHealth() - 5);
	}

	public function kaboom(Player $player, Player $d){
		$player->getWorld()->addParticle($player->getPosition(), new HugeExplodeParticle());
		$player->getWorld()->addSound($player->getPosition(), new ExplodeSound());
		//$player->knockBack($player->getPosition()->x - $d->getPosition()->x, $player->getPosition()->z - $d->getPosition()->z, 1);
		$player->setMotion($d->getDirectionVector()->add(0, 1.2, 0));
		$player->setHealth($player->getHealth() - ($player->getHealth() < 10 ? 4 : 6));
	}

	public function bleed(Player $player){
		$player->getWorld()->addParticle($player->getPosition(), new BlockBreakParticle(VanillaBlocks::CONCRETE()->setColor(DyeColor::RED())));
		$player->setHealth($player->getHealth() - 1);
	}

	public function poison(Player $player){
		$player->getEffects()->add(new EffectInstance(VanillaEffects::POISON(), 160));
	}
}