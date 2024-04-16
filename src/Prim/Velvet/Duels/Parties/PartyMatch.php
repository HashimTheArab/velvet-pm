<?php

namespace Prim\Velvet\Duels\Parties;

use pocketmine\item\ItemFactory;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Duels\Arena;
use Prim\Velvet\Duels\MatchManager;
use Prim\Velvet\Duels\NormalMatch;
use Prim\Velvet\Duels\Translator;
use Prim\Velvet\Main;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\VelvetPlayer;
use function count;
use function array_key_first;

class PartyMatch extends NormalMatch {

	public const COUNTDOWN = "countdown";
	public const FIGHTING = "fighting";

	public int $countdown = 10;
	public int $timepassed = 0;
	public string $type = "party";
	public string $status = self::COUNTDOWN;

	public array $players = [];
	public array $spectators = [];
	public array $alive = [];
	public array $dead = [];
	public array $pots = [];

	private MatchManager $manager;
	private int $identifier;
	private Arena $arena;
	private string $mode;
	private Party $party;
	private $winner = null;

	public bool $started = false;
	public bool $ended = false;

	public function __construct(MatchManager $manager, int $identifier, Arena $arena, string $mode, Party $party){
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->arena = $arena;
		$this->mode = $mode;
		$this->party = $party;
		$this->players = $party->members;
		$this->alive = $party->members;
	}

	public function getParty() : ?Party {
		return $this->party;
	}

	public function getIdentifier() : int{
		return $this->identifier;
	}

	public function getArena() : Arena{
		return $this->arena;
	}

	public function getMode() : string{
		return $this->mode;
	}

	public function getWinner() : ?Player{
		return $this->winner;
	}

	public function removePlayer(Player $player, bool $needToTeleportPlayer = false) : void {
		/** @var VelvetPlayer $player */
		$spawn = Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
		$this->setDead($player);
		if($player->isAlive()) $player->teleport($spawn);
		$this->party->sendMessage($player->getName() . ' has died! There are ' . count($this->alive) . '/' . count($this->players) . " players left in the $this->mode duel!");
		$this->pots[TF::DARK_PURPLE . $player->getName()] = TF::LIGHT_PURPLE . count($player->getInventory()->all(ItemFactory::getInstance()->get(438, 22)));
		$player->setScoreboardType(Scoreboard::NORMAL);
		if(count($this->alive) <= 1){
			$this->ended = true;
			$winner = array_key_first($this->alive);
			$this->winner = $winner ?? "Could not be determined.";
			$this->getArena()->setStatus(Translator::FREE);

			$p = Server::getInstance()->getPlayerExact($this->winner);
			if($p instanceof VelvetPlayer){
				$this->pots[TF::DARK_PURPLE . $p->getName()] = TF::LIGHT_PURPLE . count($p->getInventory()->all(ItemFactory::getInstance()->get(438,22)));
				$p->teleport($spawn);
				$p->setScoreboardType(Scoreboard::NORMAL);
				SessionManager::getInstance()->getSession($p)->setMatch(null);
			}

			$this->partyMatchEndMessage($this->party);
			foreach($this->spectators as $name => $_){
				$pl = Server::getInstance()->getPlayerExact($name);
				if($pl instanceof Player){
					if($pl->getWorld()->getFolderName() === 'duels'){
						$pl->teleport($spawn);
						$pl->setGamemode(GameMode::SURVIVAL());
					}
				}
			}

			$this->party->setMatch(null);
			$this->manager->stopMatch($this->identifier);
		}
		SessionManager::getInstance()->getSession($player)->setMatch(null);
		if($player->isImmobile()) $player->setImmobile(false);
	}

	public function partyMatchEndMessage(Party $party){
		if($this->mode === "NoDebuff") {
			$result = "";
			foreach($this->pots as $name => $pots) $result .= "$name: $pots pots\n";
			$party->sendMessage(
				"Congratulations! " . $this->winner . " has won the $this->mode duel!\n" .
				"===Match Results===\n$result"
			);
		} else {
			$party->sendMessage("Congratulations! " . $this->winner . " has won the $this->mode duel!");
		}
	}

	public function setDead(Player $player){
		$name = $player->getName();
		$this->dead[$name] = $this->players[$name];
		unset($this->alive[$name]);
	}

	/*public function doTick() : void {
		$p1 = $this->player1;
		$p2 = $this->player2;
		switch($this->status){
			case self::COUNTDOWN:
				--$this->countdown;
				if ($p1 instanceof Player && $p2 instanceof Player) {
					if ($this->countdown > 0) {
						foreach ([$p1, $p2] as $player) {
							$this->doCountdown($player);
						}
					} else {
						$this->status = self::FIGHTING;
						foreach([$p1, $p2] as $player){
							if(!$player instanceof Player) return;
							$player->setImmobile(false);
							$this->kitPlayer($player);
							$player->sendTitle(TF::AQUA . 'Fight!', '', 5, 10, 7);
						}
					}
				}
				break;
			case self::FIGHTING:
				$this->doCountUp($p1, $p2);
				break;
		}
	}*/

	public function kitPlayer(Player $player) : void {
		$kits = Translator::KITS;
		if(isset($kits[$this->mode])){
			$k = $kits[$this->mode];
			Main::getMain()->kits->$k($player);
		}
	}

}