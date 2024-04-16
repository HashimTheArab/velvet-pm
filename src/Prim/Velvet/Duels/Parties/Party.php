<?php

namespace Prim\Velvet\Duels\Parties;

use pocketmine\player\Player;
use pocketmine\Server;
use Prim\Velvet\Main;
use pocketmine\utils\TextFormat as TF;
use function count;

class Party {

	const MEMBER = "Member";
	const LEADER = "Leader";

	private Main $main;

	public string $leader;
	public int $identifier;
	public int $capacity;
	public array $members = [];
	public ?PartyMatch $match = null;

	public function __construct(int $identifier, string $leader, array $members, int $capacity){
		$this->main = Main::getMain();
		$this->identifier = $identifier;
		$this->leader = $leader;
		$this->members = $members;
		$this->capacity = $capacity;
	}

	public function getID() : int {
		return $this->identifier;
	}

	public function getLeader() : string {
		return $this->leader;
	}

	public function getMembers() : array {
		return $this->members;
	}

	public function addMember(Player $player){
		$name = $player->getName();
		$this->sendMessage("$name has joined the party!");
		$this->members[$name] = self::MEMBER;

		$session = $this->main->sessionManager->getSession($player);
		$session->setParty($this);
		$player->sendMessage(TF::GREEN . "You joined the party!");
	}

	public function removeMember(Player $player){
		$name = $player->getName();
		$this->sendMessage("$name has left the party!", TF::BOLD . TF::DARK_PURPLE . "Party " . TF::LIGHT_PURPLE . "» " . TF::RESET . TF::RED);
		if(isset($this->members[$name])){
			unset($this->members[$name]);
		}

		$this->main->sessionManager->getSession($player)->setParty(null);
		$player->sendMessage(TF::GREEN . "You left the party!");
	}

	public function kickMember(Player $player){
		$name = $player->getName();
		if(isset($this->members[$name])){
			unset($this->members[$name]);
		}

		$this->main->sessionManager->getSession($player)->setParty(null);
		$player->sendMessage(TF::RED . "You were kicked from the party!");
		$this->sendMessage("$name was kicked from the party!", TF::BOLD . TF::DARK_PURPLE . "Party " . TF::LIGHT_PURPLE . "» " . TF::RESET . TF::RED);
	}

	public function sendMessage(string $message, string $prefix = TF::BOLD . TF::DARK_PURPLE . "Party " . TF::LIGHT_PURPLE . "» " . TF::RESET . TF::LIGHT_PURPLE){
		foreach($this->members as $member => $rank){
			$member = Server::getInstance()->getPlayerExact($member);
			if($member instanceof Player){
				$member->sendMessage($prefix . $message);
			}
		}
	}

	public function disband(){
		$this->sendMessage($this->leader . " has disbanded the party!");
		foreach($this->members as $member => $rank){
			$player = Server::getInstance()->getPlayerExact($member);
			if($player instanceof Player){
				$session = $this->main->sessionManager->getSession($player);
				$session->setParty(null);
			}
		}
		if(isset(Main::getMain()->partyManager->parties[$this->identifier])){
			unset(Main::getMain()->partyManager->parties[$this->identifier]);
		}
		foreach(Main::getMain()->partyManager->getInvitesFromParty($this) as $invites){
			$invites->clear();
		}
	}

	public function getRank(Player $player) : string {
		return $this->members[$player->getName()];
	}

	public function setLeader(Player $player, $type = "left"){
		if(isset($this->members[$this->leader])) $this->members[$this->leader] = self::MEMBER;
		$name = $player->getName();
		$this->leader = $name;
		$this->members[$name] = self::LEADER;
		$this->capacity = $this->main->partyManager->getPartyCapacity($player);
		$msg = $type === "left" ? "The party leader has left. $name is the new leader!" : "$name has been promoted to Party Leader!";
		$this->sendMessage($msg);
	}

	public function isLeader(Player $player) : bool {
		return ($this->leader === $player->getName());
	}

	public function hasMember(Player $player) : bool {
		return isset($this->members[$player->getName()]);
	}

	public function isFull() : bool {
		return count($this->members) >= $this->capacity;
	}

	public function getMatch() : ?PartyMatch {
		return $this->match;
	}

	public function hasMatch() : bool {
		return $this->match != null;
	}

	public function setMatch(?PartyMatch $match){
		$this->match = $match;
	}

}