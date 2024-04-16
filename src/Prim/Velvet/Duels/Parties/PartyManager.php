<?php

namespace Prim\Velvet\Duels\Parties;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function mt_rand;

class PartyManager {

	public array $parties = [];
	public array $invites = [];

	public function createParty(Player $player){
		$name = $player->getName();

		$identifier = mt_rand(0, 1000);
		while(isset($this->parties[$identifier])) $identifier = mt_rand(0, 5000);

		$party = new Party($identifier, $name, [$name => Party::LEADER], $this->getPartyCapacity($player));
		$this->parties[$identifier] = $party;
		$session = SessionManager::getInstance()->getSession($player);
		$session->setParty($party);
		$player->sendMessage(TF::GREEN . 'Your party was created!');
		$party->sendMessage('Welcome to your party! Use * before your message to type in the party chat!');
	}

	public function invitePlayer(Party $party, Player $sender, Player $target){
		$invite = new PartyInvite($party, $sender->getName(), $target->getName());
		$this->invites[] = $invite;
		$sender->sendMessage(TF::GRAY . "You invited " . TF::LIGHT_PURPLE . $target->getName() . TF::GRAY . " to your party!");
		$target->sendMessage(TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . " has invited you to their party!");
	}

	public function getPartyCapacity(Player $player) : int {
		/** @var VelvetPlayer $player */
		$rank = SessionManager::getInstance()->getSession($player)->rank;

		if($player->hasFlag(Flags::STAFF)) return 20;
		if(isset(Translator::PARTY_SIZES[$rank])) return Translator::PARTY_SIZES[$rank];
		return 8;
	}

	public function doesExist(Party $party) : bool {
		return isset($this->parties[$party->identifier]);
	}

	public function getParty(Party $party) : ?Party {
		if(isset($this->parties[$party->identifier])) return $this->parties[$party->identifier];
		return null;
	}

	public function getInvites(Player $player) : array {
		$invites = [];
		foreach($this->invites as $invite){
			if($invite instanceof PartyInvite){
				if($invite->isTarget($player)) $invites[] = $invite;
			}
		}
		return $invites;
	}

	public function getInvitesFromParty(Party $party) : array {
		$invites = [];
		foreach($this->invites as $invite){
			if($invite instanceof PartyInvite){
				if($invite->isParty($party)) $invites[] = $invite;
			}
		}
		return $invites;
	}

	public function getInvite(int $partyId) : ?PartyInvite {
		$result = null;
		foreach($this->invites as $invite){
			if($invite instanceof PartyInvite){
				$id = $invite->getParty()->identifier;
				if($id === $partyId){
					$result = $invite;
					break;
				}
			}
		}
		return $result;
	}

	public function hasInvite(Player $target, Party $p1) : bool {
		$result = false;
		foreach($this->getInvites($target) as $invite){
			if($invite instanceof PartyInvite){
				$p2 = $invite->getParty();
				if($p1 !== null && $p1->getID() === $p2->getID()){
					$result = true;
					break;
				}
			}
		}
		return $result;
	}

}