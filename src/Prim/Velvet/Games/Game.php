<?php

namespace Prim\Velvet\Games;

use Exception;
use InvalidArgumentException;
use pocketmine\block\BlockFactory;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\Position;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_merge;
use function count;
use function explode;
use function is_null;
use function shuffle;

abstract class Game {

	public array $spectatorBlocks = [];
	public array $fighterBlocks = [];

	public array $participants = [];
	public array $fighting = [];
	public array $spectators = [];

	public bool $started = false;
	public bool $isRound = false;

	public Position $spawn;
	public Position $spawn1;
	public Position $spawn2;

	public const MINIMUM_PARTICIPANTS = 2;
	public int $minimumFighters = 2;
	public int $round = 0;
	public int $type;

	public const PREFIX = TF::GOLD . 'Events ' . TF::YELLOW . '» ';
	public const TYPE_GFIGHT = 0;
	public const TYPE_NODEBUFF = 1;
	public const TYPE_REDROVER = 2;
	public const TYPE_SUMO = 3;

	public const TYPES = [
		'GFight',
		'NoDebuff',
		'RedRover',
		'Sumo'
	];

	public const TEAM_SIZES = ['1v1', '2v2', '3v3', '4v4', '5v5', '6v6', '2 Equal Teams'];

	public const BLOCK_MAPPINGS = [
		'20:0' => '1:4',
		'241:0' => '1:0',
		'241:8' => '1:5',
		'241:7' => '4:0',
		'241:13' => '159:13',
		'241:5' => '237:13'
	];

	public const BLOCK_LOCATIONS = [
		[240, 66, 168], [240, 67, 168], [240, 66, 167], [241, 66, 167], [240, 67, 167], [240, 68, 167], [241, 66, 166],
		[241, 67, 166], [240, 68, 166], [240, 69, 166], [242, 66, 165], [242, 67, 165], [243, 66, 165], [243, 67, 165],
		[244, 66, 164], [243, 66, 164], [243, 67, 164], [244, 66, 163], [244, 67, 163], [244, 68, 163], [245, 67, 162],
		[244, 66, 162], [244, 67, 162], [244, 68, 162], [244, 69, 162], [243, 69, 163], [242, 68, 164], [242, 68, 165],
		[241, 68, 165], [241, 69, 165], [242, 69, 164], [243, 68, 163], [239, 66, 167], [239, 67, 167], [239, 68, 167],
		[239, 69, 167], [240, 66, 166], [240, 67, 166], [241, 67, 165], [241, 66, 165], [242, 66, 164], [242, 67, 164],
		[243, 66, 163], [243, 67, 163], [275, 66, 198], [275, 67, 198], [274, 66, 198], [273, 66, 199], [272, 66, 199],
		[272, 66, 200], [271, 66, 201], [270, 66, 201], [270, 66, 202], [270, 67, 202], [270, 68, 202], [269, 71, 204],
		[269, 70, 204], [269, 68, 204], [269, 69, 204], [270, 68, 203], [270, 69, 203], [270, 70, 203], [270, 71, 203],
		[271, 70, 202], [271, 69, 202], [271, 71, 202], [271, 68, 201], [271, 67, 201], [272, 67, 200], [272, 68, 200],
		[273, 67, 199], [273, 68, 199], [274, 67, 198], [274, 68, 198], [274, 69, 198], [275, 69, 198], [275, 70, 198],
		[275, 71, 199], [274, 71, 199], [274, 70, 199], [274, 69, 199], [273, 69, 200], [272, 69, 200], [272, 69, 201],
		[272, 70, 201], [273, 70, 200], [273, 71, 200], [272, 71, 201], [270, 66, 203], [270, 67, 203], [271, 66, 202],
		[271, 67, 202], [271, 68, 202], [272, 66, 201], [272, 67, 201], [272, 68, 201], [273, 66, 200], [273, 67, 200],
		[273, 68, 200], [274, 66, 199], [274, 67, 199], [274, 68, 199], [275, 68, 198], [289, 87, 154], [290, 87, 155],
		[290, 86, 155], [289, 86, 154], [289, 85, 154], [289, 82, 154], [289, 83, 154], [289, 84, 154], [289, 82, 155],
		[289, 83, 155], [290, 82, 155], [291, 82, 156], [290, 83, 155], [291, 83, 156], [290, 82, 156], [290, 83, 156],
		[290, 84, 155], [290, 85, 155], [290, 84, 156], [291, 82, 157], [292, 82, 157], [292, 83, 157], [291, 83, 157],
		[291, 84, 157], [291, 84, 156], [291, 85, 156], [291, 86, 156], [291, 87, 156], [292, 82, 158], [292, 83, 158],
		[293, 82, 158], [294, 82, 158], [294, 82, 159], [293, 82, 159], [294, 83, 159], [293, 83, 158], [293, 84, 158],
		[294, 84, 158], [294, 83, 158], [292, 84, 157], [292, 85, 157], [292, 86, 157], [291, 87, 157], [292, 87, 157],
		[294, 87, 158], [293, 87, 158], [294, 86, 158], [293, 86, 158], [294, 85, 158], [293, 85, 158], [299, 78, 158],
		[298, 78, 158], [297, 78, 158], [297, 79, 158], [298, 79, 158], [297, 80, 158], [301, 78, 158], [301, 79, 158],
		[302, 78, 158], [302, 79, 158], [303, 79, 158], [303, 80, 158], [304, 79, 158], [304, 84, 157], [303, 84, 157],
		[302, 83, 157], [303, 83, 157], [304, 83, 157], [300, 82, 157], [301, 82, 157], [302, 82, 157], [303, 82, 157],
		[304, 82, 157], [304, 81, 157], [303, 81, 157], [302, 81, 157], [301, 81, 157], [297, 81, 157], [299, 79, 157],
		[299, 80, 157], [298, 80, 157], [300, 81, 157], [299, 81, 157], [298, 81, 157], [300, 78, 157], [300, 79, 157],
		[300, 80, 157], [301, 78, 157], [301, 79, 157], [301, 80, 157], [302, 78, 157], [302, 79, 157], [302, 80, 157],
		[304, 78, 157], [304, 79, 157], [304, 80, 157], [303, 78, 157], [303, 79, 157], [303, 80, 157], [299, 78, 157],
		[298, 78, 157], [297, 78, 157], [297, 79, 157], [298, 79, 157], [297, 80, 157]
	];

	/**
	 * @throws Exception
	 */
	public function __construct(Player $owner, int $type){
		if(!isset(self::TYPES[$type])) throw new InvalidArgumentException('Invalid type!');
		Server::getInstance()->broadcastMessage(self::PREFIX . '§g' . $owner->getName() . TF::YELLOW . ' has started a ' . TF::RED . self::TYPES[$type] . TF::YELLOW . ' event! Use the book at spawn to join!');
		Server::getInstance()->broadcastTitle(TF::GREEN . 'Event: ' . self::TYPES[$type], TF::GRAY . 'Check the chat for more information.');

		$data = GameArenas::ARENAS[$type];
		Server::getInstance()->getWorldManager()->loadWorld(GameArenas::DEFAULT_LEVEL);
		$d = $data['default'];

		$level = Server::getInstance()->getWorldManager()->getWorldByName(GameArenas::DEFAULT_LEVEL);
		$this->spawn = new Position($d[0], $d[1], $d[2], $level);

		$s1 = $data['spawn1'];
		$s2 = $data['spawn2'];
		$this->spawn1 = new Position($s1[0], $s1[1], $s1[2], $level);
		$this->spawn2 = new Position($s2[0], $s2[1], $s2[2], $level);
		$this->type = $type;
		Main::getMain()->kits->initGfight();

		/*
		foreach(self::BLOCK_LOCATIONS as $location){
			$block = $level->getBlockAt($location[0], $location[1], $location[2], true, false);
			//var_dump($block->getId() . ':' . $block->getDamage());
			if(isset(self::BLOCK_MAPPINGS[$block->getId() . ':' . $block->getDamage()])){
				$a = explode(':', self::BLOCK_MAPPINGS[$block->getId() . ':' . $block->getDamage()]);
				$this->fighterBlocks[] = Block::get($a[0], $a[1], new Position($block->x, $block->y, $block->z, $block->getLevel()));
			}
		}*/
		/*
		$minX = [268, 238, 288];
		$maxX = [276, 246, 304];
		$minY = [66, 66, 78];
		$maxY = [271, 69, 88];
		$minZ = [196, 161, 153];
		$maxZ = [205, 169, 160];

		for($i = 0; $i < 3; $i++){
			for($mX = )
		}
		$this->spectatorBlocks = [];
		$this->fighterBlocks = [];
		*/
	}

	public function inEvent(Player $player) : bool {
		return isset($this->participants[$player->getName()]);
	}

	public function isFighting(Player $player) : bool {
		return isset($this->fighting[$player->getName()]);
	}

	public function isSpectating(Player $player) : bool {
		return isset($this->spectators[$player->getName()]);
	}

	public function addSpectator(Player $player){
		$player->teleport($this->spawn);
		$player->setGamemode(GameMode::SPECTATOR());
		$player->sendMessage(self::PREFIX . TF::GREEN . 'You are now spectating the ' . TF::YELLOW . self::TYPES[$this->type] . TF::GREEN . ' event!');
		if(!$this->isFighting($player) && !$this->inEvent($player)) $this->spectators[$player->getName()] = 1;
	}

	public function removeSpectator(Player $player){
		unset($this->spectators[$player->getName()]);
	}

	public function addPlayer(Player $player, bool $force = false){
		if($this->started && !$force){
			$player->sendMessage(self::PREFIX . TF::DARK_RED . 'The event has already started!');
			return;
		}
		if($this->inEvent($player)){
			$player->sendMessage(self::PREFIX . TF::RED . 'You are already in this event!');
			return;
		}
		$this->participants[$player->getName()] = 1;
		$player->sendMessage(self::PREFIX . TF::YELLOW . 'You have joined the ' . TF::RED . self::TYPES[$this->type] . TF::YELLOW . " event!");
	}

	public function removePlayer(Player $player, Player $killer = null, bool $left = false){
		unset($this->participants[$player->getName()]);
		if($this->isFighting($player)){
			unset($this->fighting[$player->getName()]);
			if(is_null($killer)){
				if($player->getName() === array_key_first($this->fighting)){
					$killer = Server::getInstance()->getPlayerExact(array_key_last($this->fighting));
				} else {
					$killer = Server::getInstance()->getPlayerExact(array_key_first($this->fighting));
				}
				# $killer = Server::getInstance()->getPlayerExact($player->getName() === array_key_first($this->fighting) ? array_key_last($this->fighting) : array_key_first($this->fighting));
			}
			if($killer->isImmobile()) $killer->setImmobile(false);
			$this->endRound($player, $killer);
		} else {
			if($left && $player->isOnline()) $player->sendMessage(self::PREFIX . TF::RED . 'You have left the event!');
		}
	}

	/**
	 * @throws Exception
	 */
	public function startRound(Player $player, Player $player1 = null, Player $player2 = null){
		if(!$this->started){
			$player->sendMessage(self::PREFIX . TF::DARK_RED . 'There is currently no event started!');
			return;
		}
		if($this->isRound){
			$player->sendMessage(self::PREFIX . TF::DARK_RED . 'There is already a round in progress!');
			return;
		}
		if(count($this->participants) > 1){
			if($player1 !== null && $player2 !== null){
				$this->doRound($player, $player1, $player2);
			} else {
				$this->doRound($player);
			}
		} else {
			$this->end($player);
		}
	}

	/**
	 * @param Player $player
	 * @param Player|null $player1
	 * @param Player|null $player2
	 * @throws Exception
	 */
	public function doRound(Player $player, Player $player1 = null, Player $player2 = null){
		$this->round++;
		$player->sendMessage(self::PREFIX . 'You have started a new round!');

		if(is_null($player1) && is_null($player2)){
			$keys = array_keys($this->participants);
			shuffle($keys);
			$random = [];
			foreach ($keys as $key) {
				$random[$key] = $this->participants[$key];
			}
			$this->participants = $random;

			$p1 = array_key_first($this->participants);
			$p2 = array_key_last($this->participants);
		} else {
			$p1 = $player1->getName();
			$p2 = $player2->getName();
		}

		$this->fighting[$p1] = 1;
		$this->fighting[$p2] = 1;

		Server::getInstance()->broadcastMessage(TF::GOLD . '[' . TF::YELLOW . self::TYPES[$this->type] . TF::GOLD . '] ' . TF::YELLOW . "$p1 vs $p2 " . TF::YELLOW . 'Round ' . TF::RED . $this->round);
		$p1 = Server::getInstance()->getPlayerExact($p1);
		$p2 = Server::getInstance()->getPlayerExact($p2);

		if(is_null($p1) || is_null($p2)) throw new Exception('Prim you fucking idiot an offline player wasnt removed from the list');

		$p1->teleport($this->spawn1);
		$p2->teleport($this->spawn2);
		$p1->setGamemode(GameMode::SPECTATOR());
		$p2->setGamemode(GameMode::SPECTATOR());
		Main::getMain()->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
		if($this->type === self::TYPE_GFIGHT) $this->sendFighterBlocks($p1, $p2);

		$this->isRound = true;
	}

	public function endRound(Player $player, Player $player2){
		$winner = array_key_first($this->fighting);
		$loser = $winner === $player->getName() ? $player2 : $player;
		$winner = Server::getInstance()->getPlayerExact($winner);

		if($winner instanceof Player){
			$winner->teleport($this->spawn);
			$winner->getInventory()->clearAll();
			$winner->getArmorInventory()->clearAll();
			$winner->getEffects()->clear();
			$winner->setHealth(20);
			if($loser->isOnline()) $loser->getEffects()->clear();
			Server::getInstance()->broadcastMessage(TF::GOLD . '[' . TF::YELLOW . self::TYPES[$this->type] . TF::GOLD . '] ' . TF::GREEN . $winner->getName() . TF::YELLOW . ' won the round against ' . TF::RED . $loser->getName() . '!');
		}
		$this->isRound = false;
		$this->fighting = [];
	}

	public function forceEndRound(Player $player){
		if(!$this->started){
			$player->sendMessage(self::PREFIX . TF::DARK_RED . 'There is currently no event started!');
			return;
		}
		if(!$this->isRound){
			$player->sendMessage(self::PREFIX . TF::DARK_RED . 'There is no round going on!');
			return;
		}
		if(count($this->participants) > 1){
			$spawn = Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
			foreach($this->fighting as $p => $_){
				$p = Server::getInstance()->getPlayerExact($p);
				$p->teleport($spawn);
			}
			$this->isRound = false;
			$this->fighting = [];
			$player->sendMessage(self::PREFIX . TF::RED . 'You have forced stopped the current round.');
		} else {
			$this->end($player);
		}
	}

	public function start(Player $player){
		if($this->started){
			$player->sendMessage(self::PREFIX . TF::DARK_RED . 'The event has already started!');
			return;
		}
		if(count($this->participants) < self::MINIMUM_PARTICIPANTS){
			$player->sendMessage(self::PREFIX . TF::GRAY . 'You need at least ' . TF::LIGHT_PURPLE . self::MINIMUM_PARTICIPANTS . TF::GRAY . " people to start this event!");
			return;
		}

		$type = self::TYPES[$this->type];

		Server::getInstance()->broadcastMessage(self::PREFIX . "§g" . $player->getName() . TF::YELLOW . " has begun the " . TF::RED . $type . TF::YELLOW . " event!");

		Main::getMain()->game = $this;
		$this->started = true;

		foreach($this->participants as $p => $_){
			$p = Server::getInstance()->getPlayerExact($p);
			$p->teleport($this->spawn);
			$p->setGamemode(GameMode::SPECTATOR());
			//$this->sendSpectatorBlocks($p);
		}

		foreach(self::BLOCK_LOCATIONS as $location){
			$block = Server::getInstance()->getWorldManager()->getWorldByName(Translator::EVENTS_WORLD)->getBlockAt($location[0], $location[1], $location[2], true, false);
			//var_dump($block->getId() . ':' . $block->getDamage());
			if(isset(self::BLOCK_MAPPINGS[$block->getId() . ':' . $block->getMeta()])){
				$a = explode(':', self::BLOCK_MAPPINGS[$block->getId() . ':' . $block->getMeta()]);
				$this->fighterBlocks[] = BlockFactory::getInstance()->get($a[0], $a[1], new Position($block->getPosition()->x, $block->getPosition()->y, $block->getPosition()->z, $block->getPosition()->getWorld()));
			}
		}
	}

	public function end(Player $player){
		$type = self::TYPES[$this->type];
		$player->sendMessage(self::PREFIX . 'You have ended the ' . TF::RED . $type . TF::YELLOW . ' event!');
		if(count($this->participants) === 1){
			$winner = array_key_first($this->participants);
			Server::getInstance()->broadcastMessage(self::PREFIX . 'The ' . TF::RED . $type . TF::YELLOW . ' event is now over! The winner is ' . TF::GREEN . "$winner!");
		} else {
			if(!$this->started){
				Server::getInstance()->broadcastMessage(self::PREFIX . 'The ' . TF::RED . $type . TF::YELLOW . ' event was closed by ' . TF::RED . $player->getName() . '!');
			} else {
				Server::getInstance()->broadcastMessage(self::PREFIX . 'The ' . TF::RED . $type . TF::YELLOW . ' event is over. A winner could not be determined');
			}
		}
		$spawn = Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
		$players = array_merge(array_keys($this->participants), array_keys($this->spectators));
		foreach($players as $p) Server::getInstance()->getPlayerExact($p)->teleport($spawn);
		Main::getMain()->kits->_gfight = [];
		Main::getMain()->game = null;
	}

	public function sendSpectatorBlocks(Player $player) : void {
		/*$packets = [];
		foreach($this->spectatorBlocks as $block){
			$pk = new UpdateBlockPacket();
			$pk->x = $block->x;
			$pk->y = $block->y;
			$pk->z = $block->z;
			$pk->blockRuntimeId = $block->getRuntimeId();
			$pk->flags = UpdateBlockPacket::FLAG_NONE;
			$packets[] = $pk;
		}
		Server::getInstance()->batchPackets([$player], $packets);*/
	}

	/**
	 * @param ...$players Player
	 */
	public function sendFighterBlocks(...$players) : void {
		/*$packets = [];
		foreach($this->fighterBlocks as $block){
			$pk = new UpdateBlockPacket();
			$pk->x = $block->x;
			$pk->y = $block->y;
			$pk->z = $block->z;
			$pk->blockRuntimeId = $block->getRuntimeId();
			$pk->flags = UpdateBlockPacket::FLAG_NONE;
			$packets[] = $pk;
		}
		Server::getInstance()->batchPackets($players, $packets);*/
	}

}