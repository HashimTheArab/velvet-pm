<?php

namespace Prim\Velvet\Games;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\sound\XpCollectSound;
use Prim\Velvet\Duels\Translator;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Kits;
use Prim\Velvet\Main;

class GameTask extends Task {

	public Game $game;
	public Kits $kits;
	public int $countdown = 11;
	public string $mode;

	/** @var Player[] */
	public array $players = [];

	public function __construct(Game $game){
		$this->game = $game;
		$this->kits = Main::getMain()->kits;
		$this->mode = Game::TYPES[$game->type];
		foreach($game->fighting as $name => $_) $this->players[$name] = Server::getInstance()->getPlayerExact($name);
	}

	public function onRun() : void {
		if($this->countdown > 1){
			$this->countdown--;
			foreach($this->players as $player){
				if(!$player->isOnline() || !$player->isAlive() && !$this->game instanceof TeamedGame){
					$this->getHandler()->cancel();
					return;
				}
				$player->setImmobile();
				$player->sendTitle($this->countdown > 5 ? TF::YELLOW . $this->countdown : TF::YELLOW . $this->countdown . '...');
				$player->broadcastSound(new NoteSound(NoteInstrument::PIANO(), 1));
			}
		} else {
			$k = Main::getMain()->kits;
			foreach($this->players as $player){
				$player->setImmobile(false);
				$kits = Translator::KITS;
				if(isset($kits[$this->mode])) $k->{$kits[$this->mode]}($player);
				$player->sendTitle(TF::LIGHT_PURPLE . 'Fight!', '', 5, 10, 7);
				$player->broadcastSound(new XpCollectSound());
			}
			$this->getHandler()->cancel();
		}
	}
}