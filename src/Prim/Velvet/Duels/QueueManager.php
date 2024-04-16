<?php

namespace Prim\Velvet\Duels;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use function in_array;
use function array_shift;
use function count;
use function is_null;

class QueueManager {

	public array $queue = ['NoDebuff' => [], 'Gapple' => [], 'Diamond' => [], 'Sumo' => [], 'Line' => [], 'God' => []];
	private Main $main;

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function inQueue(Player $player, ?string $mode = null) : bool {
		if($mode !== null){
			return isset($this->queue[$mode][$player->getName()]);
		}
		$name = $player->getName();
		foreach($this->queue as $queue){
			if(in_array($name, $queue)) return true;
		}
		return false;
	}

	public function addPlayer(Player $player, string $mode) : void {
		$session = $this->main->sessionManager->getSession($player);
		$arena = $this->main->arenaManager->getArenaByMode($mode);
		if($session->hasMatch()) return;
		if(is_null($arena)){
			$player->sendMessage(TF::DARK_RED . "All $mode arenas are currently full! Please join back or request more arenas on discord.");
			return;
		}
		if($this->inQueue($player)){
			$player->sendMessage(TF::DARK_RED . 'You are already in the queue!');
			return;
		}
		$player->sendMessage(TF::GREEN . "You have been added to the $mode queue!");
		$this->queue[$mode][$player->getName()] = $player->getName();
		$this->searchForDuel($mode);
	}

	public function removePlayer(Player $player, ?string $mode = null) : void {
		if($mode !== null){
			if(!$this->inQueue($player)){
				$player->sendMessage(TF::DARK_RED . 'You are not in the queue!');
			} else {
				unset($this->queue[$mode][$player->getName()]);
			}
			return;
		}
		foreach($this->queue as $mode => $_){
			if($this->inQueue($player, $mode)){
				unset($this->queue[$mode][$player->getName()]);
			}
		}
	}

	public function searchForDuel(string $mode) : void {
		$server = $this->main->getServer();
		if(count($this->queue[$mode]) >= 2){
			$player1 = $server->getPlayerExact(array_shift($this->queue[$mode]));
			$player2 = $server->getPlayerExact(array_shift($this->queue[$mode]));
			$this->main->matchManager->startMatch($player1, $player2, $mode);
			$this->addPlayer($player1, $mode);
			$this->addPlayer($player2, $mode);
		}
	}
}