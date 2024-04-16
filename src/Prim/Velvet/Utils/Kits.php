<?php

namespace Prim\Velvet\Utils;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Kits {

	const SPAWN_NAMES = [0 => '§aArenas!', 1 => '§3Duels!', 2 => '§6Bot Fights!', 4 => '§dProfile!', 6 => '§gEvents!' , 7 => '§dParty!', 8 => '§9Toys!'];

	const GOD_ENCHANTS = [69 => 3, 70 => 3, 75 => 3, 74 => 3, 72 => 2, 73 => 2, 78 => 2];
	const GOD_LORE_ONE = ['§6Kaboom III', '§eZeus III', '§eBleed III', '§2Hades III', '§2Poison II', '§2Lifesteal II', '§aOOF II'];
	const GOD_LORE_TWO = ['§6Overlord II', '§2Adrenaline I', '§aScorch V'];
	const GOD_LORE_THREE = ['§6Overlord II', '§eGears I', '§2Adrenaline I', '§aScorch V'];

	public array $_nodebuff = [];
	public array $_gapple = [];
	public array $_diamond = [];
	public array $_god = [];
	public array $_build = ['extra' => []];
	public array $_world = [];
	public array $_gfight = [];

	public function __construct(){
		$this->initNodebuff();
		$this->initGapple();
		$this->initDiamond();
		$this->initGod();
		$this->initBuild();
		$this->initWorld();
	}

	public function Sumo(Player $player) : void {
		$inv = $player->getInventory();
		$inv->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), 6000, 2, false));
	}

	public function initGfight() : void {
		$slot = [VanillaItems::DIAMOND_SWORD()->setCustomName('§l§4GKit')->setLore(self::GOD_LORE_ONE), VanillaItems::GOLDEN_APPLE()->setCustomName('§l§4GKit'), VanillaItems::DIAMOND_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::DIAMOND_LEGGINGS(), VanillaItems::DIAMOND_BOOTS()];

		foreach(self::GOD_ENCHANTS as $id => $level){
			$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING()), 3);
			$slot[0]->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId($id), $level));
		}

		foreach([$slot[2], $slot[3], $slot[4], $slot[5]] as $armor) {
			$armor->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
			foreach([82 => 2, 85 => 1, 84 => 5] as $id => $level){
				$armor->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId($id), $level));
			}
			$armor->setLore(self::GOD_LORE_TWO);
			$armor->setCustomName('§l§4GKit');
		}

		$slot[5]->setLore(self::GOD_LORE_THREE);

		$slot[5]->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(79), 1));
		$this->_gfight = $slot;
	}

	public function gfight(Player $player) : void {
		$inv = $player->getInventory();
		$player->getArmorInventory()->clearAll();
		$inv->clearAll();

		$player->getArmorInventory()->setContents([$this->_gfight[2], $this->_gfight[3], $this->_gfight[4], $this->_gfight[5]]);
		$inv->setItem(0, $this->_gfight[0]);
		$inv->setItem(1, $this->_gfight[1]);
	}

	public function initNodebuff() : void {
		$slot = [
			VanillaItems::DIAMOND_SWORD()->setCustomName('§l§5Nodebuff'), VanillaItems::ENDER_PEARL()->setCount(16), VanillaItems::DIAMOND_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::DIAMOND_LEGGINGS(),
			VanillaItems::DIAMOND_BOOTS(), ItemFactory::getInstance()->get(438, 22, 34)
		];

		$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
		$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

		#Armor
		foreach([$slot[2], $slot[3], $slot[4], $slot[5]] as $armor) {
			$armor->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
			$armor->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
			$armor->setCustomName('§l§5Nodebuff');
		}

		$this->_nodebuff = $slot;
	}

	public function nodebuff(Player $player) : void {
		$inv = $player->getInventory();
		$inv->clearAll();
		$player->getArmorInventory()->clearAll();

		$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 0, false));

		$player->getArmorInventory()->setContents([$this->_nodebuff[2], $this->_nodebuff[3], $this->_nodebuff[4], $this->_nodebuff[5]]); #Armor
		#Inventory
		$inv->setItem(0, $this->_nodebuff[0]); #Diamond Sword
		$inv->setItem(1, $this->_nodebuff[1]); #EnderPearl
		$inv->addItem($this->_nodebuff[6]); #Pots
	}

	public function initGapple() : void {
		$slot = [VanillaItems::DIAMOND_SWORD()->setCustomName('§l§6Gapple'), VanillaItems::GOLDEN_APPLE()->setCount(12)->setCustomName('§l§6Gapple'), VanillaItems::DIAMOND_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::DIAMOND_LEGGINGS(), VanillaItems::DIAMOND_BOOTS(), VanillaItems::MILK_BUCKET()];

		#Sword
		$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
		$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

		#Armor
		foreach([$slot[2], $slot[3], $slot[4], $slot[5]] as $armor) {
			$armor->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
			$armor->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
			$armor->setCustomName('§l§6Gapple');
		}

		$this->_gapple = $slot;
	}

	public function gapple(Player $player) : void {
		$inv = $player->getInventory();
		$inv->clearAll();
		$player->getArmorInventory()->clearAll();

		$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 0, false));

		$player->getArmorInventory()->setContents([$this->_gapple[2], $this->_gapple[3], $this->_gapple[4], $this->_gapple[5]]); #Armor
		#Inventory
		$inv->setItem(0, $this->_gapple[0]); #Diamond Sword
		$inv->setItem(1, $this->_gapple[1]); #Golden Apple
		$inv->setItem(8, $this->_gapple[6]); #Bucket of Milk
	}

	public function initDiamond() : void {
		$slot = [VanillaItems::DIAMOND_SWORD(), VanillaItems::BOW(), VanillaItems::DIAMOND_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::DIAMOND_LEGGINGS(), VanillaItems::DIAMOND_BOOTS(), VanillaItems::ARROW()];

		#Sword
		$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
		$slot[0]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

		#Armor
		foreach([$slot[2], $slot[3], $slot[4], $slot[5]] as $armorslots) {
			$armorslots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1)); #Protection
			$armorslots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10)); #Unbreaking
		}

		$this->_diamond = $slot;
	}

	public function diamond(Player $player) : void {
		$inv = $player->getInventory();
		$inv->clearAll();
		$player->getArmorInventory()->clearAll();

		$player->getArmorInventory()->setContents([$this->_diamond[2], $this->_diamond[3], $this->_diamond[4], $this->_diamond[5]]); #Armor

		$inv->setItem(0, $this->_diamond[0]); #Diamond Sword
		$inv->setItem(1, $this->_diamond[1]); #Bow
		$inv->setItem(9, $this->_diamond[6]); #Arrow
	}

	public function initGod() : void {
		$slot = [VanillaItems::DIAMOND_SWORD(), VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(6), VanillaItems::DIAMOND_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::DIAMOND_LEGGINGS(), VanillaItems::DIAMOND_BOOTS()];

		#Armor
		foreach([$slot[2], $slot[3], $slot[4], $slot[5]] as $armorslots) {
			$armorslots->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)); #Unbreaking
		}
		$this->_god = $slot;
	}

	public function god(Player $player) : void {
		$inv = $player->getInventory();
		$inv->clearAll();
		$player->getArmorInventory()->clearAll();

		$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 999999, 0, false));
		//$player->addEffect(new EffectInstance(VanillaEffects::JUMP_BOOST(), 999999, 0, false));

		$player->getArmorInventory()->setContents([$this->_god[2], $this->_god[3], $this->_god[4], $this->_god[5]]); #Armor
		#Inventory
		$inv->setItem(0, $this->_god[0]); #Diamond Sword
		$inv->setItem(1, $this->_god[1]); #Egaps
	}

	public function initBuild() : void {
		$slot = [VanillaItems::DIAMOND_HELMET(), VanillaItems::DIAMOND_CHESTPLATE(), VanillaItems::DIAMOND_LEGGINGS(), VanillaItems::DIAMOND_BOOTS(), VanillaItems::DIAMOND_SWORD(), VanillaItems::BOW(), VanillaItems::DIAMOND_AXE(), VanillaItems::DIAMOND_PICKAXE(), VanillaItems::WOODEN_HOE()->setDamage(58)];

		# Sword
		$slot[4]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1));
		$slot[4]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

		# Bow
		$slot[5]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
		$slot[5]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

		# Axe and Pickaxe
		$slot[6]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3));
		$slot[7]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));

		# Hoe
		$slot[8]->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1));

		# Armor
		foreach([$slot[0], $slot[1], $slot[2], $slot[3]] as $armorSlot){
			$armorSlot->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1));
			$armorSlot->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 10));
		}

		$this->_build = $slot;
		$this->_build['extra'] = [2 => VanillaItems::GOLDEN_APPLE()->setCount(10), 3 => VanillaItems::ENDER_PEARL()->setCount(10), 6 => VanillaBlocks::COBBLESTONE()->asItem()->setCount(64), 7 =>VanillaBlocks::OAK_PLANKS()->asItem()->setCount(64), 9 => VanillaItems::ARROW()->setCount(16)];
	}

	public function build(Player $player) : void {
		$inv = $player->getInventory();
		$player->getArmorInventory()->clearAll();
		$inv->clearAll();

		$player->getArmorInventory()->setContents([$this->_build[0], $this->_build[1], $this->_build[2], $this->_build[3]]); #Armor
		#Inventory
		$inv->setItem(0, $this->_build[4]); #Diamond Sword
		$inv->setItem(1, $this->_build[5]); #Bow
		$inv->setItem(4, $this->_build[6]); #Diamond Axe
		$inv->setItem(5, $this->_build[7]); #Diamond Pickaxe
		$inv->setItem(8, $this->_build[8]); #Wooden Hoe

		foreach ($this->_build['extra'] as $slot => $item) $inv->setItem($slot, $item);
	}

	public function Oitc(Player $player) : void {
		$inv = $player->getInventory();
		$player->getArmorInventory()->clearAll();
		$inv->clearAll();

		$player->getArmorInventory()->setContents([VanillaItems::IRON_HELMET(), VanillaItems::IRON_CHESTPLATE(), VanillaItems::IRON_LEGGINGS(), VanillaItems::IRON_BOOTS()]);
		$inv->setItem(0, VanillaItems::STONE_SWORD());
		$inv->setItem(1, VanillaItems::BOW());
		$inv->setItem(2, VanillaItems::ARROW());
	}

	public function initWorld() : void {
		$array = [0 => VanillaItems::DIAMOND_SWORD(), 1 => VanillaItems::IRON_SWORD(), 2 => VanillaItems::GOLDEN_AXE(), 4 => VanillaItems::PLAYER_HEAD(), 6 => ItemFactory::getInstance()->get(403), 7 => VanillaItems::NETHER_STAR(), 8 => VanillaItems::NETHER_STAR()];
		$glow = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(-1));
		foreach ($array as $slot => $item){
			/** @var $item Item */
			$item->setCustomName(self::SPAWN_NAMES[$slot]);
			$item->addEnchantment($glow);
		}
		$this->_world = $array;
	}

	public function worlditems(Player $player) : void {
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getInventory()->setHeldItemIndex(0);
		foreach ($this->_world as $slot => $item) $player->getInventory()->setItem($slot, $item);
	}

}