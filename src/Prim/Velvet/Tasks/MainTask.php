<?php

namespace Prim\Velvet\Tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function count;
use function in_array;

class MainTask extends Task {

	public int $runs = 86400;

	const BROADCAST_HOURS = [43200, 21600, 3600]; //hours:
	const BROADCAST_MINUTES = [1800, 600, 300, 60]; //minutes: 30, 10, 5, 1
	const BROADCAST_SECONDS = [30, 10, 5, 4, 3, 2, 1];
	const ALL_TIMES = [43200, 21600, 1800, 600, 300, 60, 30, 10, 5, 4, 3, 2, 1];

	public Server $server;
	public Scoreboard $scoreboard;

	public function __construct(public Main $main){
		$this->server = $main->getServer();
		$this->scoreboard = Scoreboard::getInstance();
	}

	public function onRun() : void {
		foreach($this->main->taggedPlayers as $name => $time) {
			$time--;
			$player = $this->server->getPlayerExact($name);
			if($player instanceof Player){
				$player->getXpManager()->setXpLevel($time);
				if($time <= 0) $player->sendMessage(TF::GREEN . 'You are no longer in combat!');
			}
			if($time <= 0) {
				$this->main->setTagged($name, false);
			} else {
				$this->main->taggedPlayers[$name]--;
			}
		}

		foreach($this->server->getOnlinePlayers() as $p){
			/** @var VelvetPlayer $p */
			if($p->spawned && $p->hasFlag(Flags::VANISHED)){
				$p->sendActionBarMessage('§aYou are currently vanished!');
				foreach($p->getWorld()->getPlayers() as $online){
					$online->hasPermission('velvet.vanish') ? $online->showPlayer($p) : $online->hidePlayer($p);
				}
			}
		}

		$this->scoreboard->onlinePlayers = count($this->server->getOnlinePlayers());
		$this->scoreboard->tps = $this->server->getTicksPerSecond();

		if($this->runs > 0){
			$this->runs--;
			if($this->runs <= 43200){
				if(in_array($this->runs, self::ALL_TIMES)){

					$message = Translator::VELVET_PREFIX . 'The server will restart in ' . TF::DARK_PURPLE;

					if(in_array($this->runs, self::BROADCAST_HOURS)) {
						$message .= $this->runs / 3600 . TF::LIGHT_PURPLE;
						$message .= $this->runs != 3600 ? " hours!" : "hour!";
					} elseif(in_array($this->runs, self::BROADCAST_MINUTES)){
						$message .= $this->runs / 60 . TF::LIGHT_PURPLE;
						$message .= $this->runs != 60 ? " minutes!" : " minute!";
					} elseif(in_array($this->runs, self::BROADCAST_SECONDS)){
						$message .= $this->runs . TF::LIGHT_PURPLE;
						$message .= $this->runs != 1 ? " seconds!" : " second!";
					}

					$this->server->broadcastMessage($message);
				}
			}
		} else {
			$this->server->broadcastMessage(Translator::VELVET_PREFIX . 'The server is now restarting!');
			foreach($this->server->getOnlinePlayers() as $p) $p->kick(Translator::SERVER_RESTARTING, false);
			$this->main->getServer()->shutdown();
		}

	}

}