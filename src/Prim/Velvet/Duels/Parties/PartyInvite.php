<?php

namespace Prim\Velvet\Duels\Parties;

use pocketmine\player\Player;
use pocketmine\Server;
use Prim\Velvet\Main;
use pocketmine\utils\TextFormat as TF;
use function array_search;

class PartyInvite {

	private Main $main;
	public Party $party;
	public string $sender;
	public string $target;

	public function __construct(Party $party, string $sender, string $target){
		$this->main = Main::getMain();
		$this->party = $party;
		$this->sender = $sender;
		$this->target = $target;
	}

	public function getParty() : ?Party {
		return $this->party;
	}

	public function getSender() : ?Player {
		return Server::getInstance()->getPlayerExact($this->sender);
	}

	public function getTarget() : ?Player {
		return Server::getInstance()->getPlayerExact($this->target);
	}

	public function isTarget(Player $player) : bool {
		return $player->getName() === $this->target;
	}

	public function isSender(Player $player) : bool {
		return $player->getName() === $this->sender;
	}

	public function isParty(Party $party) : bool {
		return $party->getID() === $this->party->getID();
	}

	public function clear(){
		$pm = $this->main->partyManager;
		if(isset($pm->invites[array_search($this, $pm->invites)])){
			unset($pm->invites[array_search($this, $pm->invites)]);
		}
	}

	public function accept(){
		$sender = Server::getInstance()->getPlayerExact($this->sender);
		$target = Server::getInstance()->getPlayerExact($this->target);
		if($sender instanceof Player) $sender->sendMessage(TF::GREEN . $target->getName() . " has accepted your party invitation!");
		if($target instanceof Player) $target->sendMessage(TF::GREEN . "You have accepted the party invitation!");
		if($this->doesExist()){
			$this->party->addMember($target);
		} else {
			$target->sendMessage(TF::RED . "That party no longer exists!");
		}
		$this->clear();
	}

	public function decline(){
		$sender = Server::getInstance()->getPlayerExact($this->sender);
		$target = Server::getInstance()->getPlayerExact($this->target);
		if($sender instanceof Player) $sender->sendMessage(TF::RED . $target->getName() . " declined your party invitation!");
		if($target instanceof Player) $target->sendMessage(TF::RED . "You have declined the party invitation!");
		$this->clear();
	}

	public function doesExist() : bool{
		return Main::getMain()->partyManager->doesExist($this->party);
	}

}