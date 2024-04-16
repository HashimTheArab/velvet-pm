<?php

namespace Prim\Velvet\Bots;

use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use Prim\Velvet\Duels\Arena;
use Prim\Velvet\Duels\NormalMatch;
use Prim\Velvet\Duels\MatchManager;
use Prim\Velvet\Duels\Translator;
use Prim\Velvet\Main;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\VelvetPlayer;
use function count;
use function round;

class BotMatch extends NormalMatch {

	public const COUNTDOWN = 'countdown';
	public const FIGHTING = 'fighting';
	public const TYPE = 'bot';

	public int $countdown = 2;
	public int $timepassed = 0;
	public string $type = 'bot';
	public string $status = self::COUNTDOWN;

	private MatchManager $manager;
	private int $identifier;
	private Arena $arena;
	private string $mode;
	private Player $player;
	private Bot $bot;
	private $winner = null;

	public bool $started = false;
	public bool $ended = false;

	public function __construct(MatchManager $manager, int $identifier, Arena $arena, string $mode, Player $player, Bot $bot){
		$this->manager = $manager;
		$this->identifier = $identifier;
		$this->arena = $arena;
		$this->mode = $mode;
		$this->player = $player;
		$this->bot = $bot;
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

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getBot() : Bot {
		return $this->bot;
	}

	public function getStatus() : string{
		return $this->status;
	}

	public function getWinner() : ?Player{
		return $this->winner;
	}

	public function doTick() : void {
		/*if($this->status == self::COUNTDOWN){
			--$this->countdown;
			if ($this->player->isOnline()) {
				if ($this->countdown <= 0) {
					$this->status = self::FIGHTING;
					$this->player->setImmobile(false);
				}
			}
		}*/
	}

	public function removePlayer(Player $player) : void {
		$this->winner = $this->bot->isAlive() ? $this->bot : $this->player;
		$this->ended = true;
		$pots = count($player->getInventory()->all(ItemFactory::getInstance()->get(438,22)));
		$name = $player->getName();
		$pots2 = $this->bot->getPots();

		if($player === $this->winner){
			$hp = round($player->getHealth(), 2);
			$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
			Server::getInstance()->broadcastMessage(TF::GRAY . "$name [$hp] [$pots Pots] won a fight against the {$this->bot->difficulty} bot [$pots2 Pots]!");
		} else {
			$hp = round($this->bot->getHealth(), 2);
			Server::getInstance()->broadcastMessage(TF::GRAY . "$name [$pots Pots] lost a fight against the {$this->bot->difficulty} bot [$hp] [$pots2 Pots]!");
			if(!$this->bot->isClosed()) $this->bot->flagForDespawn();
		}

		SessionManager::getInstance()->getSession($player)->setMatch(null);
		/** @var VelvetPlayer $player */
		$player->setScoreboardType(Scoreboard::NORMAL);
		if($player->isImmobile()) $player->setImmobile(false);

		$this->getArena()->setStatus(Translator::FREE);
		$this->manager->stopMatch($this->identifier);
	}

	public function doCountdown(Player $player) : void {
		if($player->isOnline()){
			$player->broadcastSound(new NoteSound(NoteInstrument::PIANO(), 1));
			$player->sendTitle($this->countdown > 3 ? TF::AQUA . $this->countdown : TF::AQUA . $this->countdown . '...');
		}
	}

	public function kitPlayer(Player $player) : void {
		Main::getMain()->kits->nodebuff($player);
	}
}