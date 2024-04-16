<?php

namespace Prim\Velvet\Duels;

use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use Prim\Velvet\Main;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\VelvetPlayer;
use function count;
use function gmdate;

class NormalMatch {

	public const COUNTDOWN = "countdown";
	public const FIGHTING = "fighting";

	public int $countdown = 6;
	public int $timepassed = 0;
	public string $type = "unranked";
	public string $status = self::COUNTDOWN;

	private MatchManager $manager;
	private int $identifier;
	private Arena $arena;
	private string $mode;
	private Player $player1;
	private Player $player2;
	private $winner = null;

	public bool $started = false;
	public bool $ended = false;

	public function __construct(MatchManager $manager, int $identifier, Arena $arena, string $mode, Player $player1, Player $player2){
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->arena = $arena;
		$this->mode = $mode;
		$this->player1 = $player1;
		$this->player2 = $player2;
	}

	public function getIdentifier() : int {
		return $this->identifier;
	}

	public function getArena() : Arena {
		return $this->arena;
	}

	public function getMode() : string {
		return $this->mode;
	}

	public function getPlayer1() : Player {
		return $this->player1;
	}

	public function getPlayer2() : Player {
		return $this->player2;
	}

	public function getStatus() : string {
		return $this->status;
	}

	public function getWinner() : ?Player {
		return $this->winner;
	}

	public function doTick() : void {
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
							$player->sendTitle(TF::LIGHT_PURPLE . 'Fight!', '', 5, 10, 7);
						}
					}
				}
				break;
			/*case self::FIGHTING:
				$this->doCountUp($p1, $p2);
				break;*/
		}
	}

	public function removePlayer(Player $player) : void {
		$winner = $player === $this->player1 ? $this->player2 : $this->player1;
		$this->ended = true;
		$this->matchendmessage($player, $winner);
		$winner->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
		$sm = SessionManager::getInstance();
		foreach([$winner, $player] as $participant){
			/** @var VelvetPlayer $participant */
			$sm->getSession($participant)->setMatch(null);
			$participant->setScoreboardType(Scoreboard::NORMAL);
			if($participant->isImmobile()) $participant->setImmobile(false);
		}
		Server::getInstance()->broadcastMessage(TF::GRAY . $winner->getName() . " won an unranked $this->mode duel against " . $player->getName() . '!');
		$this->winner = $winner;
		$this->getArena()->setStatus(Translator::FREE);
		$this->manager->stopMatch($this->identifier);
	}

	public function matchendmessage(Player $player, ?Player $winner) : void {
		$pots = count($player->getInventory()->all(ItemFactory::getInstance()->get(438, 22)));
		$wpots = count($winner->getInventory()->all(ItemFactory::getInstance()->get(438, 22)));
		$name = $player->getName();
		$wname = $winner->getName();
		if($this->mode === "NoDebuff") {
			$player->sendMessage(TF::AQUA . "===Match Results===\n" . "$name: " . TF::WHITE . "$pots Pots\n" . TF::AQUA .
				"$wname: " . TF::WHITE . "$wpots Pots\n" . TF::AQUA . "The winner of this match is: " . TF::WHITE . "$wname!"
			);
			$winner->sendMessage(TF::AQUA . "===Match Results===\n" . "$wname: " . TF::WHITE . "$wpots Pots\n" . TF::AQUA .
				"$name: " . TF::WHITE . "$pots Pots\n" . TF::AQUA . "The winner of this match is: " . TF::WHITE . "$wname!"
			);
		}
	}

	public function doCountdown(Player $player) : void {
		if($player !== null){
			$player->getWorld()->addSound($player->getPosition(), new NoteSound(NoteInstrument::PIANO(), 3));
			$player->sendTitle($this->countdown > 3 ? TF::AQUA . $this->countdown : TF::AQUA . $this->countdown . '...');
		}
	}

	public function getOpponent(Player $player) : ?Player {
		return $this->player1 === $player ? $this->player2 : $this->player1;
	}

	public function doCountUp(Player $player, Player $player2) : void {
		$this->timepassed++;
		$formatted = gmdate('i:s', $this->timepassed);
		$player->sendPopup(TF::AQUA . "Duration: $formatted");
		$player2->sendPopup(TF::AQUA . "Duration: $formatted");
	}

	public function kitPlayer(Player $player) : void {
		$kits = Translator::KITS;
		if(isset($kits[$this->mode])){
			$k = $kits[$this->mode];
			Main::getMain()->kits->$k($player);
		}
	}

}