<?php

namespace Prim\Velvet\Entities\FloatingText;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use Prim\Velvet\Main;
use Ramsey\Uuid\Uuid;
use function is_null;

class TextObject {

	//Credit to BoomYourBang for this whole class
	public string $name;

	public string $text = "";
	public Position $position;
	public int $eid;

	public Main $main;

	public function __construct(string $name, Position $position){
		$this->eid = Entity::nextRuntimeId();
		$this->name = $name;
		$this->position = $position;

		$this->main = Main::getMain();
	}

	public function getName() : string{
		return $this->name;
	}

	public function getText() : string{
		return $this->text;
	}

	public function getId() : int{
		return $this->eid;
	}

	public function getPosition() : Position{
		return $this->position;
	}

	public function canUpdate() : bool {
		$pos = $this->getPosition();
		$entity = $pos->getWorld()->getNearestEntity($pos, 3, Player::class, false);

		return $entity !== null;
	}

	public function spawnTo(Player $player){
		$flags = 0;
		$flags |= 1 << EntityMetadataFlags::IMMOBILE;

		$collection = new EntityMetadataCollection();
		$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
		$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
		$collection->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
		$collection->setString(EntityMetadataProperties::NAMETAG, $this->text);

		$pk = AddPlayerPacket::create(Uuid::uuid4(), $this->getText(), $this->getId(), $this->getId(), '', $this->getPosition(), null, 0, 0, 0, ItemStackWrapper::legacy(TypeConverter::getInstance()->coreItemStackToNet(VanillaBlocks::AIR()->asItem())), $collection->getAll(), AdventureSettingsPacket::create(0, 0, 0, 0, 0, $this->getId()), [], '', DeviceOS::UNKNOWN);
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function update($players = [], $force = false, $overwrite = null){
		if(empty($players)){
			$players = Server::getInstance()->getOnlinePlayers();
		}

		if($players instanceof Player){
			$players = [$players];
		}

		if(is_null($overwrite)){
			$text = $this->updateText();
		} else {
			$text = $overwrite;
		}

		if(!$force && !$this->canUpdate()) return;

		$flags = 0;
		$flags |= 1 << EntityMetadataFlags::IMMOBILE;

		$collection = new EntityMetadataCollection();
		$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
		$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
		$collection->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
		$collection->setString(EntityMetadataProperties::NAMETAG, $text);

		$pk = SetActorDataPacket::create($this->getId(), $collection->getAll(), 0);
		foreach($players as $player){
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function hide($players = [], $force = false){
		if(empty($players)){
			$players = Server::getInstance()->getOnlinePlayers();
		}
		if($players instanceof Player){
			$players = [$players];
		}

		if(!$force && !$this->canUpdate()) return;

		$flags = 0;
		$flags |= 1 << EntityMetadataFlags::IMMOBILE;
		$flags |= 1 << EntityMetadataFlags::INVISIBLE;

		$collection = new EntityMetadataCollection();
		$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
		$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
		$collection->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
		$collection->setString(EntityMetadataProperties::NAMETAG, '');

		$pk = SetActorDataPacket::create($this->getId(), $collection->getAll(), 0);
		foreach($players as $player){
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function show($players = [], $force = false){
		if(empty($players)){
			$players = Server::getInstance()->getOnlinePlayers();
		}

		if($players instanceof Player){
			$players = [$players];
		}

		if(!$force && !$this->canUpdate()) return;

		$flags = 0;
		$flags |= 1 << EntityMetadataFlags::IMMOBILE;

		$collection = new EntityMetadataCollection();
		$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
		$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
		$collection->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
		$collection->setString(EntityMetadataProperties::NAMETAG, $this->text);

		$pk = SetActorDataPacket::create($this->getId(), $collection->getAll(), 0);
		foreach($players as $player) $player->getNetworkSession()->sendDataPacket($pk);
	}

	public function updateText() : string {
		$titles = ["kills" => "Top Kills", "deaths" => "Top Deaths", "kdr" => "Top KDR", "topkillstreak" => "Alltime Top Killstreaks"];

		$result = $this->main->entityManager->leaderboardInfo[$this->getName()];

		$f = "";
		$i = 0;
		foreach($result as $name => $stat){
			$i++;
			$f .= "§7$i. §d$name: §b$stat\n";
		}

		$text = "§l§d" . $titles[$this->getName()] . "\n$f";

		$this->text = $text;
		return $this->getText();
	}
}