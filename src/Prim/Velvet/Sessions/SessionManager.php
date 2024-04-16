<?php

namespace Prim\Velvet\Sessions;

use pocketmine\player\Player;
use pocketmine\Server;

class SessionManager {

	public static self $instance;
	private array $sessions = [];

	public function __construct(){
		self::$instance = $this;
	}

	public function getSessions() : array{
		return $this->sessions;
	}

	public function getSession(Player $player) : ?Session {
		return $this->sessions[$player->getName()] ?? null;
	}

	public function getSessionByName(string $name) : ?Session{
		$player = Server::getInstance()->getPlayerExact($name);
		if($player !== null){
			return $this->getSession($player);
		}
		return null;
	}

	public function createSession(Player $player) : Session {
		$this->sessions[$player->getName()] = new Session($player);
		return $this->sessions[$player->getName()];
	}

	public function closeSession(Player $player) : void {
		$name = $player->getName();
		if(isset($this->sessions[$name])){
			foreach($this->sessions as $session){
				if(!$session instanceof Session) return;
				if(!$this->sessions[$name] instanceof Session) return;
				if($session->hasInviteFrom($this->sessions[$name])) $session->clearInvitesFrom($this->sessions[$name]);
				if($session->getLastInviteOwner() === $this->sessions[$name]->getOwner()) $session->setLastInviteOwner(null);
			}
			$this->sessions[$name]->clearAllInvites();
			unset($this->sessions[$name]);
		}
	}

	public static function getInstance() : self {
		return self::$instance;
	}

}