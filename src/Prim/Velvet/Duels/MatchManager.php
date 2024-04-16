<?php

namespace Prim\Velvet\Duels;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator as UT;
use Prim\Velvet\Bots\BotMatch;
use Prim\Velvet\Bots\Bot;
use Prim\Velvet\Duels\Parties\Party;
use Prim\Velvet\Duels\Parties\PartyMatch;
use Prim\Velvet\Main;
use Prim\Velvet\Tasks\MatchTask;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\VelvetPlayer;
use function mt_rand;
use function array_rand;
use function is_null;

class MatchManager {

	/** @var NormalMatch[] */
	public array $matches = [];
	public Main $main;

	public function __construct(Main $main){
		$this->main = $main;
		$this->main->getScheduler()->scheduleRepeatingTask(new MatchTask($this), 20);
	}

	public function getMatches() : array{
		return $this->matches;
	}

	public function getMatch(int $identifier) : ?NormalMatch {
		return $this->matches[$identifier] ?? null;
	}

	public function startMatch(Player $player1, Player $player2, string $mode, ?Arena $arena = null) : void {
		$identifier = mt_rand(0, 1000);
		while(isset($this->matches[$identifier])) $identifier = mt_rand(0, 5000);

		$s1 = $this->main->sessionManager->getSession($player1);
		$s2 = $this->main->sessionManager->getSession($player2);
		$qm = $this->main->queueManager;
		$match = new NormalMatch($this, $identifier, ($arena ?? $this->main->arenaManager->getArenaByMode($mode)), $mode, $player1, $player2);
		$arena = $match->getArena();
		$arena->setStatus(Translator::BUSY);
		$s1->setMatch($match);
		$s2->setMatch($match);
		$name1 = $player1->getName();
		$name2 = $player2->getName();
		$ping1 = $player1->getNetworkSession()->getPing();
		$ping2 = $player2->getNetworkSession()->getPing();

		$player1->teleport($arena->getSpawn1());
		$player1->sendMessage(
			TF::AQUA . "Match found!\n" . TF::WHITE . "------------------\n" .
			"Mode: " . TF::AQUA . "$mode\n" . TF::WHITE . "Opponent: " . TF::AQUA .
			"$name2\n" . TF::WHITE . "Your Ping: " . TF::AQUA . "{$ping1}ms\n" .
			TF::WHITE . "Their Ping: " . TF::AQUA . "{$ping2}ms"
		);

		$player2->teleport($arena->getSpawn2());
		$player2->sendMessage(
			TF::AQUA . "Match found!\n" . TF::WHITE . "------------------\n" .
			"Mode: " . TF::AQUA . "$mode\n" . TF::WHITE . "Opponent: " . TF::AQUA .
			"$name1\n" . TF::WHITE . "Your Ping: " . TF::AQUA . "{$ping2}ms\n" .
			TF::WHITE . "Their Ping: " . TF::AQUA . "{$ping1}ms"
		);

		foreach([$player1, $player2] as $player){
			/** @var VelvetPlayer $player */
			$player->setImmobile();
			if($qm->inQueue($player, $mode)) $qm->removePlayer($player, $mode);
			$player->setScoreboardType(Scoreboard::MATCH);
		}

		$match->started = true;
		$this->matches[$identifier] = $match;
	}

	public function startPartyMatch(Party $party, string $mode, ?Arena $arena = null) : void {
		$identifier = mt_rand(0, 1000);
		while(isset($this->matches[$identifier])) $identifier = mt_rand(0, 5000);

		$match = new PartyMatch($this, $identifier, ($arena ?? $this->main->arenaManager->getArenaByMode($mode)), $mode, $party);
		$arena = $match->getArena();
		$arena->setStatus(Translator::BUSY);
		$party->setMatch($match);

		$spawns = [$arena->getSpawn1(), $arena->getSpawn2()];
		foreach($match->players as $member => $rank){
			$player = Server::getInstance()->getPlayerExact($member);
			if($player instanceof VelvetPlayer){
				$session = $this->main->sessionManager->getSession($player);
				$session->setMatch($match);
				$player->teleport($spawns[array_rand($spawns)]);
				$match->kitPlayer($player);
				$player->setScoreboardType(Scoreboard::PARTY_MATCH);
			}
		}

		$match->started = true;
		$this->matches[$identifier] = $match;
	}

	public function startBotMatch(Player $player, string $mode, ?Arena $arena = null) : void {
		$type = BotMatch::TYPE;

		$a = $this->main->arenaManager->getArenaByMode($type);
		if(is_null($a)){
			$player->sendMessage(TF::DARK_RED . "All $type arenas are currently full! Please join back or request more arenas on discord.");
			return;
		}

		$identifier = mt_rand(0, 1000);
		while(isset($this->matches[$identifier])) $identifier = mt_rand(0, 5000);

		$bot = new Bot($player->getLocation(), $player->getSkin());
		$bot->setTargetEntity($player);
		$bot->reach = UT::BOT_REACH[$mode];
		$bot->blocksRunToPot = UT::BOT_BLOCKS_RUN_TO_POT[$mode];
		$bot->damage = UT::BOT_DAMAGE[$mode];
		$bot->difficulty = $mode;
		if($mode === 'Clown' || $mode === 'Easy') $bot->nextStrafeTicks = PHP_INT_MAX;
		$bot->started = true;

		$match = new BotMatch($this, $identifier, ($arena ?? $this->main->arenaManager->getArenaByMode($type)), $mode, $player, $bot);
		$arena = $match->getArena();
		$arena->setStatus(Translator::BUSY);

		$session = $this->main->sessionManager->getSession($player);
		$session->setMatch($match);

		$player->teleport($arena->getSpawn2());
		$player->sendMessage(TF::RED . 'Disclaimer: ' . TF::GRAY . 'The bots are still experimental! They will be vastly improved soon.');
		$player->sendMessage(TF::GREEN . 'You are fighting a bot, good luck!');
		//$player->setImmobile();

		$bot->teleport($arena->getSpawn2());

		$qm = $this->main->queueManager;
		$match->kitPlayer($player);
		if($qm->inQueue($player, $type)) $qm->removePlayer($player, $type);

		$match->started = true;
		$this->matches[$identifier] = $match;
	}

	public function stopMatch(int $identifier) : void {
		if(isset($this->matches[$identifier])) unset($this->matches[$identifier]);
	}

}