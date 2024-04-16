<?php

namespace Prim\Velvet\Utils;

use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\Position;
use Prim\Velvet\Forms\CustomForm;
use Prim\Velvet\Forms\ModalForm;
use Prim\Velvet\Forms\SimpleForm;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Bots\BotMatch;
use Prim\Velvet\Duels\Parties\Party;
use Prim\Velvet\Duels\Parties\PartyInvite;
use Prim\Velvet\Games\Game;
use Prim\Velvet\Games\Normal\PvPGame;
use Prim\Velvet\Main;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\VelvetPlayer;
use function array_keys;
use function count;
use function implode;
use function in_array;
use function is_null;
use function mt_rand;
use function str_contains;
use function str_repeat;
use function preg_match;
use function strlen;
use function trim;

class Forms {

	public function showEvents(Player $player) : void {
		$game = Main::getMain()->game;
		$form = new SimpleForm(function (Player $player, string $result = null) use ($game){
			if(is_null($result)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no event going on!');
				return;
			}
			switch($result){
				case 'join':
					$game->addPlayer($player);
					break;
				case 'leave':
					if(!$game->inEvent($player)){
						$player->sendMessage(Game::PREFIX . TF::RED . 'You are not in the event!');
						return;
					}
					$game->removePlayer($player, null, true);
					break;
				case 'spectate':
					$game->addSpectator($player);
					break;
			}
		});

		$form->setTitle(TF::BOLD . TF::GOLD . 'Events!');
		if($game !== null){
			if($game->started){
				$form->setContent(Game::PREFIX . 'The event has already started! You may spectate it.');
				$form->addButton(TF::YELLOW . 'Spectate', -1, '', 'spectate');
			} else {
				if($game->inEvent($player)){
					$form->setContent(Game::PREFIX . TF::GOLD . 'You are in the ' . Game::TYPES[$game->type] . ' event!');
					$form->addButton(TF::RED . 'Leave the event', -1, '', 'leave');
				} else {
					$form->setContent(Game::PREFIX . 'There is a ' . TF::RED . Game::TYPES[$game->type] . TF::YELLOW . ' event going on!');
					$form->addButton(TF::GREEN . 'Join the event', -1, '', 'join');
				}
			}
		} else {
			$form->setContent(TF::GRAY . "There are currently no events going on." . str_repeat("\n", 10));
			$form->addButton('Go Back!', -1, '', 'back');
		}

		$player->sendForm($form);
	}

	public function gameCreateForm(Player $player) : void {
		$form = new CustomForm(function (Player $player, array $data = null){
			if(is_null($data)) return;
			if(is_null(Main::getMain()->game)){
				if($data[0] === Game::TYPE_REDROVER){
					$player->sendMessage(Game::PREFIX . TF::RED . 'That is not implemented yet!');
					return;
				}
				if($data[0] === Game::TYPE_GFIGHT && is_null(Server::getInstance()->getPluginManager()->getPlugin('ATEnchants'))){
					$player->sendMessage(Game::PREFIX . TF::RED . 'The ATEnchants plugin must be loaded to start this event!');
					return;
				}
				Main::getMain()->game = new PvPGame($player, $data[0]);
			} else {
				$player->sendMessage(Game::PREFIX . TF::DARK_RED . 'There is already an event going on!');
			}
		});

		$form->setTitle(TF::GOLD . 'Events!');
		$form->addDropdown(TF::YELLOW . 'Event Mode', Game::TYPES);
		$form->addDropdown(TF::YELLOW . 'Team Sizes', Game::TEAM_SIZES);

		$player->sendForm($form);
	}

	public function manageGame(Player $player) : void {
		$game = Main::getMain()->game;
		$form = new SimpleForm(function (Player $player, string $data = null) use ($game){
			if(is_null($data)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}
			switch($data){
				case 'start':
					$game->start($player);
					break;
				case 'rounds':
					if(!$game->started){
						$player->sendMessage(Game::PREFIX . TF::RED . 'The event has not yet started!');
						return;
					}
					$this->manageRound($player);
					break;
				case 'addplayer':
					$this->gameAddPlayer($player);
					break;
				case 'confirm':
					$this->confirmEndEvent($player);
					break;
			}
		});

		$form->setTitle(TF::GOLD . 'Manage Events!');
		$form->setContent(TF::GOLD . 'Manage the ' . TF::RED . Game::TYPES[$game->type] . TF::GOLD . ' event!');
		if(!$game->started){
			$form->addButton(TF::GREEN . 'Start the event.', -1, '', 'start');
		} else {
			$form->addButton(TF::YELLOW . 'Round Manager', -1, '', 'rounds');
		}
		$form->addButton(TF::YELLOW . 'Add a Player', -1, '', 'addplayer');
		$form->addButton(TF::RED . 'End the event', -1, '', 'confirm');

		$player->sendForm($form);
	}

	public function gameAddPlayer(Player $player) : void {
		$game = Main::getMain()->game;

		$players = [];
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			if(!$game->inEvent($p)) $players[] = $p->getName();
		}

		$form = new CustomForm(function (Player $player, array $data = null) use ($game, $players){
			if(is_null($data) || count($players) < 1) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}
			$target = Server::getInstance()->getPlayerExact($players[$data[0]]);
			if(is_null($target)){
				$player->sendMessage(TF::RED . 'That player is offline!');
				return;
			}
			if($game->inEvent($target)){
				$player->sendMessage(TF::RED . 'That player is already in the event!');
				return;
			}
			$game->addPlayer($target, true);
			$player->sendMessage(TF::GREEN . 'You have added ' . TF::YELLOW . $target->getName() . TF::GREEN . ' to the event!');
		});

		$form->setTitle(TF::GOLD . 'Add a Player!');
		$form->addDropdown('Select a player!', $players);

		$player->sendForm($form);
	}

	public function confirmEndEvent(Player $player) : void {
		$game = Main::getMain()->game;
		$form = new ModalForm(function (Player $player, bool $result = null) use ($game){
			if(is_null($result)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}

			if($result){
				$game->end($player);
			} else {
				$this->manageGame($player);
			}
		});

		$form->setTitle(TF::RED . 'Confirmation!');
		$form->setContent(TF::DARK_RED . 'Are you sure you want to end the ' . Game::TYPES[$game->type] . ' event?');
		$form->setButton1(TF::RED . 'Yes');
		$form->setButton2(TF::GREEN . 'No, go back!');

		$player->sendForm($form);
	}

	public function manageRound(Player $player){
		$game = Main::getMain()->game;
		$form = new SimpleForm(function (Player $player, string $data = null) use ($game){
			if(is_null($data)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}
			if(!$game->started && $data !== 'back'){
				$player->sendMessage(Game::PREFIX . TF::RED . 'The game has not yet begun!');
				return;
			}
			switch($data){
				case 'start':
					if(!$game->isRound){
						$this->roundStartForm($player);
					} else {
						$player->sendMessage(TF::DARK_RED . 'There is currently a round going on!');
					}
					break;
				case 'stop':
					$this->roundForceEndConfirm($player);
					break;
				case 'back':
					$this->manageGame($player);
					break;
			}
		});

		$form->setTitle(TF::GOLD . 'Round Manager!');
		if(!$game->isRound){
			$form->addButton(TF::GREEN . 'Start a new round!', -1, '', 'start');
		} else {
			$form->addButton(TF::RED . 'Force stop the current round!', -1, '', 'stop');
		}
		$form->addButton('Go Back', -1, '', 'back');

		$player->sendForm($form);
	}

	public function roundStartForm(Player $player) : void {
		$game = Main::getMain()->game;
		$form = new SimpleForm(function (Player $player, string $data = null) use ($game){
			if(is_null($data)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}
			if(!$game->started && $data !== 'back'){
				$player->sendMessage(Game::PREFIX . TF::RED . 'The game has not yet begun!');
				return;
			}
			switch($data){
				case 'random':
					$game->startRound($player);
					break;
				case 'custom':
					$this->choosePlayersGameForm($player);
					break;
				case 'back':
					$this->manageRound($player);
					break;
			}
		});

		$form->setTitle(TF::GOLD . 'Round Manager!');
		$form->addButton(TF::GREEN . 'Random Matchup', -1, '', 'random');
		$form->addButton(TF::DARK_GREEN . 'Custom Matchup', -1, '', 'custom');
		$form->addButton('Go Back', -1, '', 'back');

		$player->sendForm($form);
	}

	public function choosePlayersGameForm(Player $player) : void {
		$game = Main::getMain()->game;
		$form = new CustomForm(function (Player $player, array $data = null) use ($game) : void {
			if(is_null($data)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}
			if(!$game->started){
				$player->sendMessage(Game::PREFIX . TF::RED . 'The game has not yet begun!');
				return;
			}

			$list = array_keys($game->participants);
			$players = [];
			foreach($data as $value){
				if(in_array($list[$value], $players)){
					$player->sendMessage(Game::PREFIX . TF::RED . 'You cannot select the same player more than once!');
					return;
				}
				$players[] = $list[$value];
			}

			if(count($players) < $game->minimumFighters){
				$player->sendMessage(Game::PREFIX . TF::RED . 'Not enough players were selected!');
				return;
			}

			$p1 = Server::getInstance()->getPlayerExact($players[0]);
			$p2 = Server::getInstance()->getPlayerExact($players[1]);

			if(is_null($p1) || is_null($p2)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'One of the selected players is no longer online!');
				return;
			}

			if(!$game->inEvent($p1) || !$game->inEvent($p2)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'One of the selected players is no longer in the event!');
				return;
			}


			$game->startRound($player, $p1, $p2);
		});

		$form->setTitle(TF::GOLD . 'Player Selection');
		for($i = 0; $i < $game->minimumFighters; $i++){
			$form->addDropdown('Select a Player', array_keys($game->participants), null, $i);
		}

		$player->sendForm($form);
	}

	public function roundForceEndConfirm(Player $player) : void {
		$game = Main::getMain()->game;
		$form = new ModalForm(function (Player $player, bool $result = null) use ($game){
			if(is_null($result)) return;
			if(is_null($game)){
				$player->sendMessage(Game::PREFIX . TF::RED . 'There is no game currently going on!');
				return;
			}
			if(!$game->started){
				$player->sendMessage(Game::PREFIX . TF::RED . 'The game has not yet begun!');
				return;
			}

			if($result){
				$game->forceEndRound($player);
			} else {
				$this->manageRound($player);
			}
		});

		$form->setTitle(TF::RED . 'Confirmation!');
		$form->setContent(TF::DARK_RED . 'Are you sure you want to force end the current round? ' . TF::GRAY . 'Fighters: [' . implode(array_keys($game->fighting)) . ']');
		$form->setButton1(TF::RED . 'Yes');
		$form->setButton2(TF::GREEN . 'No, go back!');

		$player->sendForm($form);
	}

	public function duels(Player $player) : void {
		$main = Main::getMain();
		$qm = $main->queueManager;
		$form = new SimpleForm(function (Player $player, $result = null) use ($qm){
			if(is_null($result)) return;
			switch ($result){
				case "modes":
					$this->modes($player);
					break;
				case "leave":
					if($qm->inQueue($player)) {
						$qm->removePlayer($player);
						$player->sendMessage(TF::RED . "You have left the queue!");
					}
					break;
			}
		});

		$uc = count($main->matchManager->matches);
		$qd = 0;
		foreach($qm->queue as $queued) $qd = $qd + count($queued);

		$form->setTitle("§l§5Duels!");
		$form->setContent("§bChoose a mode to play!");
		$form->addButton("Unranked\n§bMatches: $uc Queued: $qd", -1, "", "modes");
		if($qm->inQueue($player)) $form->addButton(TF::RED . "Leave queue!", -1, "", "leave");

		$player->sendForm($form);
	}

	public function modes(Player $player) : void {
		$qm = Main::getMain()->queueManager;
		$form = new SimpleForm(function (Player $player, int $result = null) use ($qm){
			if(is_null($result)) return;
			$mode = Translator::DUEL_MODES[$result];
			if($qm->inQueue($player)) {
				$player->sendMessage(TF::RED . "You are already in a queue!");
			} else {
				$qm->addPlayer($player, $mode);
			}
		});
		$queue = $qm->queue;
		$n = count($queue["NoDebuff"]);
		$g = count($queue["Gapple"]);
		$d = count($queue["Diamond"]);
		$s = count($queue["Sumo"]);
		$god = count($queue["God"]);
		$l = count($queue["Line"]);
		$form->setTitle("§l§5Duels!");
		$form->setContent("§bChoose a mode to play!");
		$form->addButton("§l§9Nodebuff§r\n§bQueued: $n", 0, "textures/items/potion_bottle_splash_heal");
		$form->addButton("§l§6Gapple§r\n§bQueued: $g", 0, "textures/items/apple_golden");
		$form->addButton("§l§bDiamond§r\n§bQueued: $d", 0, "textures/items/diamond_sword");
		$form->addButton("§l§4God§r\n§bQueued: $god", 0, "textures/items/nether_star");
		$form->addButton("§l§3Sumo§r\n§bQueued: $s", 0, "textures/ui/slow_falling_effect");
		$form->addButton("§l§2Line§r\n§bQueued: $l", 0, "textures/items/lead");

		$player->sendForm($form);
	}

	public function botduels(Player $player) : void {
		$form = new SimpleForm(function (Player $player, $result = null) : void {
			if(is_null($result)) return;
			if($player->getWorld()->getFolderName() !== Translator::LOBBY_WORLD) {
				$player->sendMessage(TF::RED . 'You must be at the lobby to use this!');
				return;
			}
			$arena = Main::getMain()->arenaManager->getArenaByMode(BotMatch::TYPE);
			if(is_null($arena)){
				$player->sendMessage(TF::DARK_RED . "All bot arenas are currently full! Please join back or request more arenas on discord.");
				return;
			}
			Main::getMain()->matchManager->startBotMatch($player, Translator::BOT_MODES[$result], $arena);
		});

		$form->setTitle("§l§5Bot Duels!");
		foreach(Translator::BOT_MODES as $mode){
			$form->addButton(TF::LIGHT_PURPLE . $mode, 0, "textures/items/potion_bottle_splash_heal");
		}

		$player->sendForm($form);
	}

	public function form(Player $player) : void {
		$server = Server::getInstance();
		$manager = $server->getWorldManager();
		$form = new SimpleForm(function (Player $player, int $result = null) use($server, $manager){
			if(is_null($result)) return;
			switch($result){
				case 3:
					$player->teleport(new Position(321, 74, 201, $server->getWorldManager()->getWorldByName(Translator::BUILDUHC_WORLD)));
					break;
				case 5:
					$level = $manager->getWorldByName(Translator::OITC_WORLD);
					$x = mt_rand(260, 400);
					$z = mt_rand(110, 250);
					$y = $level->getHighestBlockAt($x, $z);
					$player->teleport(new Position($x, $y + 2, $z, $level));
					break;
				default:
					$player->teleport($manager->getWorldByName(Translator::FFA_WORLDS[$result])->getSafeSpawn());
			}
		});

		$form->setTitle('§l§dFree For All!');
		$form->setContent('§aChoose a mode to play!');
		$form->addButton("§l§9Nodebuff\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::NODEBUFF_WORLD)->getPlayers()), 0, "textures/items/potion_bottle_splash_heal");
		$form->addButton("§l§6Gapple\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::GAPPLE_WORLD)->getPlayers()), 0, "textures/items/apple_golden");
		$form->addButton("§l§bDiamond\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::DIAMOND_WORLD)->getPlayers()), 0, "textures/items/diamond_sword");
		$form->addButton("§l§5Build\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::BUILDUHC_WORLD)->getPlayers()), 0, "textures/blocks/planks_oak");
		$form->addButton("§l§4God\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::GOD_WORLD)->getPlayers()), 0, "textures/items/nether_star");
		$form->addButton("§l§aOne in The Quiver\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::OITC_WORLD)->getPlayers()), 0, "textures/items/arrow");
		$form->addButton("§l§3Lobby\n" . Translator::CURRENTLY_PLAYING . count($manager->getWorldByName(Translator::LOBBY_WORLD)->getPlayers()), 0, "textures/blocks/barrier");

		$player->sendForm($form);
	}

	public function toys(Player $player) : void {
		$form = new SimpleForm(function (Player $player, int $result = null){
			if(is_null($result)) return;
			switch ($result){
				case 0:
					$this->scale($player);
					break;
				case 1:
					$player->hasPermission('cape.use') ? $this->capes($player) : $player->sendMessage(TF::YELLOW . 'You need' . TF::LIGHT_PURPLE . ' VIP ' . TF::YELLOW . 'or higher to use this! Buy a rank upgrade at ' . TF::LIGHT_PURPLE . Translator::SHOP_LINK . '!');
					break;
				case 2:
					$player->hasPermission('pot.color') ? $this->potcolor($player) : $player->sendMessage(TF::YELLOW . 'You need' . TF::LIGHT_PURPLE . ' Ravager ' . TF::YELLOW . 'or higher to use this! Buy a rank upgrade at ' . TF::LIGHT_PURPLE . Translator::SHOP_LINK . '!');
					break;
				case 3:
					$this->potding($player);
					break;
			}
		});
		$form->setTitle("§l§bToys / Cosmetics!");
		$form->setContent("§bChoose a category!");
		$form->addButton("§fPlayer Size");
		$form->addButton("§fCapes");
		$form->addButton("§fPotion Splash");
		$form->addButton("§fPotion Ding");

		$player->sendForm($form);
	}

	public function scale(Player $player) : void {
		$form = new SimpleForm(function (Player $player, int $result = null){
			if(is_null($result)) return;
			if($player->getWorld()->getFolderName() === Translator::LOBBY_WORLD) $player->setScale(Translator::SCALES[$result]);
		});
		$form->setTitle('§l§bToys!');
		$form->setContent('§bPlayer Size');
		foreach(Translator::SIZES as $size) $form->addButton($size);

		$player->sendForm($form);
	}

	public function potding(Player $player) : void {
		/** @var VelvetPlayer $player */
		$form = new CustomForm(function (Player $player, array $data = null) : void {
			if(is_null($data)) return;
			/** @var VelvetPlayer $player */
			if($data[1]){
				if($player->hasFlag(Flags::POTDING)) return;
				$player->setFlag(Flags::POTDING);
				$player->sendMessage(TF::GREEN . 'You have turned your potion ding cosmetic on!');
			} else {
				if(!$player->hasFlag(Flags::POTDING)) return;
				$player->setFlag(Flags::POTDING);
				$player->sendMessage(TF::RED . 'You have turned your potion ding cosmetic off!');
			}
		});

		$form->setTitle('§l§bCosmetics');
		$form->addLabel('§fPotion Ding is a cosmetic that plays a ding noise when your potion hits your body!');
		$form->addToggle('Potion Ding', $player->hasFlag(Flags::POTDING));

		$player->sendForm($form);
	}

	public function stats(Player $player) : void {
		$form = new SimpleForm(function (Player $player, int $result = null){
			if(is_null($result)) return;
			switch ($result){
				case 0:
					$this->statss($player);
					break;
				case 1:
					$this->settings($player);
					break;
			}
		});

		$form->setTitle("§l§dProfile!");
		$form->setContent("§bSee your stats or change your settings!" . str_repeat("\n", 6));
		$form->addButton("Stats!");
		$form->addButton("Settings!");

		$player->sendForm($form);
	}

	public function statss(Player $player, Player $target = null) : void {
		$form = new SimpleForm(function (Player $player, int $result = null){
			if(is_null($result)) return;
			switch ($result){
				case 0:
					$this->stats($player);
					break;
			}
		});

		$session = SessionManager::getInstance()->getSession($player);

		$form->setTitle(is_null($target) ? TF::LIGHT_PURPLE . 'Profile!' : TF::GOLD . $target->getName() . '\'s Profile!');
		$form->setContent(
			TF::LIGHT_PURPLE . 'Kills ' . TF::GRAY . "$session->kills\n" .
			TF::LIGHT_PURPLE . 'Deaths ' . TF::GRAY . "$session->deaths\n" .
			TF::LIGHT_PURPLE . 'KDR ' . TF::GRAY . "{$session->getKillToDeathRatio()}\n\n" .
			TF::LIGHT_PURPLE . 'Killstreak ' . TF::GRAY . "$session->killstreak\n" .
			TF::LIGHT_PURPLE . 'Top Killstreak ' . TF::GRAY . "$session->topKillstreak\n"
		);
		$form->addButton('Go back!');

		is_null($target) ? $player->sendForm($form) : $target->sendForm($form);
	}

	public function settings(Player $player) : void {
		$form = new SimpleForm(function (Player $player, ?string $result = null) : void {
			if(is_null($result)) return;
			switch ($result){
				case 'rank':
					$this->manageCustomRankForm($player);
					break;
				case 'tag':
					if(!$player->hasPermission('tag.change')){
						$player->sendMessage(TF::RED . "This is only available if you've bought a tag! Buy a tag at velvet.tebex.io.");
						return;
					}
					$this->changetag($player);
					break;
				case 'back':
					$this->stats($player);
					break;
			}
		});

		$form->setTitle('§l§6Settings');
		$form->setContent('Manage your player settings here!');
		if(SessionManager::getInstance()->getSession($player)->rank === 'Custom'){
			$form->addButton('§eModify your rank', label: 'rank');
		}
		$form->addButton('§eChange your tag', label: 'tag');
		$form->addButton('§eGo back', label: 'back');

		$player->sendForm($form);
	}

	public function settingsForm(Player $player) : void {
		$main = Main::getMain();
		/** @var VelvetPlayer $player */
		$session = $main->sessionManager->getSession($player);
		$capes = array_keys($main->cosmeticManager->capes);
		$form = new CustomForm(function (Player $player, array $data = null) use ($main, $session, $capes){
			if(is_null($data)) return;
			/** @var VelvetPlayer $player */
			if(!$player->hasFlag(Flags::POTDING) && $data['ding']){
				$player->setFlag(Flags::POTDING);
			} elseif($player->hasFlag(Flags::POTDING) && !$data['ding']){
				$player->setFlag(Flags::POTDING);
			}
			if($player->hasPermission('cape.use')){
				if($data['cape'] === 0) {
					$main->cosmeticManager->setCape($player, '');
				} else {
					if($main->cosmeticManager->createCape($player, $capes[$data['cape']])){
						$session->cape = $capes[$data['cape']];
					} else {
						$player->sendMessage(TF::RED . 'The cape you selected is currently unavailable!');
					}
				}
			} else {
				$player->sendMessage(TF::YELLOW . 'You need' . TF::LIGHT_PURPLE . ' VIP ' . TF::YELLOW . 'or higher to use this! Buy a rank upgrade at ' . TF::LIGHT_PURPLE . Translator::SHOP_LINK . '!');
			}
			$player->sendMessage(TF::GREEN . 'Your settings have been updated!');
		});

		$form->setTitle(TF::YELLOW . TF::BOLD . 'Settings!');
		/*$form->addDropdown("Pot Color Type", ["Preset", "Custom"], null, "colorType");
		$form->addStepSlider("Pot Color", ["Default " . Emojis::RED, "Blue " . Emojis::BLUE, "Green " . Emojis::GREEN, "Orange " . Emojis::ORANGE, "Yellow " . Emojis::YELLOW, "Purple " . Emojis::PURPLE]);
		$form->addLabel("Custom Potion Color");
		$form->addSlider("Red", 0, 255);
		$form->addSlider("Green", 0, 255);
		$form->addSlider("Blue", 0, 255);*/
		$form->addToggle('Potion Hit Ding', $player->hasFlag(Flags::POTDING), 'ding');
		$form->addDropdown('Cape', $capes, 0, 'cape');

		$player->sendForm($form);
	}

	public function manageCustomRankForm(Player $player) : void {
		$session = SessionManager::getInstance()->getSession($player);
		$form = new CustomForm(function (Player $player, array $data = null) use($session) : void {
			if(is_null($data)) return;
			if($session->rank !== 'Custom'){
				$player->sendMessage(TF::RED . 'You do not have permission to use this!');
				return;
			}
			if(!str_contains($data[1], '{display_name}') && !str_contains(Utils::clean($data[1]), $player->getName())){
				$player->sendMessage(TF::RED . 'Your chat format must either contain your IGN or the tag ' . TF::GRAY . '{display_name}!');
				return;
			}
			if(strlen(trim($data[1], '§')) > 100){
				$player->sendMessage(TF::RED . 'Your chat format must be under 100 characters!');
				return;
			}
			if(!str_contains($data[2], '{display_name}') && !str_contains(Utils::clean($data[2]), $player->getName())){
				$player->sendMessage(TF::RED . 'Your nametag must either contain your IGN or the tag ' . TF::GRAY . '{display_name}!');
				return;
			}
			if(strlen(trim($data[2], '§')) > 30){
				$player->sendMessage(TF::RED . 'Your nametag must be under 30 characters!');
				return;
			}
			$session->customRankChat = $data[1];
			$session->customRankNameTag = $data[2];
			$player->sendMessage(TF::GREEN . 'Congratulations! Your settings have been set!');
		});

		$form->setTitle(TF::GOLD . 'Custom Rank!');
		$form->addLabel(TF::YELLOW . "Thank you for purchasing this private rank!\n\nAvailable Tags: " . TF::LIGHT_PURPLE . "{kills}, {deaths}, {display_name}, {msg}, {prefix}\n\n" . 'Kills: ' . TF::YELLOW . "The amount of kills you have\n" . TF::LIGHT_PURPLE . 'Deaths: ' . TF::YELLOW . "The amount of deaths you have\n" . TF::LIGHT_PURPLE . 'Display_Name: ' . TF::YELLOW . "Your IGN or Nickname\n" . TF::LIGHT_PURPLE . 'Msg: ' . TF::YELLOW . 'Your chat message ' . TF::RED . "(Chat Only)\n" . TF::LIGHT_PURPLE . 'Prefix: ' . TF::YELLOW . 'Your custom tag if you have one ' . TF::RED . "(Chat Only)\n");
		$form->addInput(TF::LIGHT_PURPLE . 'Chat Format', TF::GRAY . 'Enter a chat format', $session->customRankChat);
		$form->addInput(TF::LIGHT_PURPLE . 'Nametag Format', TF::GRAY . 'Enter a nametag', $session->customRankNameTag);

		$player->sendForm($form);
	}

	public function changetag(Player $player) : void {
		$form = new CustomForm(function (Player $player, array $data = null){
			if(!$player->hasPermission('tag.change')){
				$player->sendMessage(TF::RED . 'This is only available if you\'ve bought a tag! Buy a tag at velvet.tebex.io!');
				return;
			}
			if(is_null($data)) return;
			$pr = Main::getMain()->permissionManager;
			if($data[0] === ''){
				$player->sendmessage(TF::YELLOW . 'Your tag has been removed!');
				$pr->setNode($player, PermissionManager::NODE_TAG, null);
				return;
			}
			if(preg_match('/[^a-zA-Z0-9§]+/', $data[0])){
				$player->sendMessage(TF::RED . 'Your tag cannot contain any special characters!');
				return;
			}
			if(strlen($data[0]) > 15){
				$player->sendMessage(TF::RED . 'Your tag must be under 15 characters!');
				return;
			}
			$player->sendMessage(TF::GREEN . 'Your tag has been changed to ' . TF::YELLOW . $data[0] . '!');
			$pr->setNode($player, PermissionManager::NODE_TAG, $data[0] . TF::RESET . ' ');
		});

		$form->setTitle('§l§6Change your tag!');
		$form->addInput('Leave it empty to reset your tag', 'Legend','');

		$player->sendForm($form);
	}

	public function potcolor(Player $player) : void {
		$form = new SimpleForm(function (Player $player, int $result = null){
			if(is_null($result)){
				return;
			}
			switch ($result){
				case 0:
					SessionManager::getInstance()->getSession($player)->potColor = [];
					$player->sendMessage(TF::GREEN . "You have changed your pot splash color back to default!");
					break;
				case 6:
					if(!$player->hasPermission("pot.custom")){
						$player->sendMessage(TF::RED . "Custom colors are only available to rank Hyperedge! Buy a rank upgrade at velvet.tebex.io!");
						return;
					}
					$this->customrgb($player);
					break;
				default:
					Main::getMain()->setPotionColor($player, Translator::POT_COLORS[$result]);
					$player->sendMessage(TF::GREEN . "You have updated your potion splash color!");
			}
		});

		$form->setTitle('§l§6Custom Potion Colors');
		$form->setContent("Choose a custom splash color or make your own!");
		foreach(Translator::POT_COLOR_BUTTONS as $button) $form->addButton($button);

		$player->sendForm($form);
	}

	public function customrgb(Player $player) : void {
		$form = new CustomForm(function (Player $player, array $data = null){
			if(is_null($data)){
				return;
			}
			Main::getMain()->setPotionColor($player, [$data[0], $data[1], $data[2]]);
			$player->sendMessage(TF::GREEN . 'You have updated your custom potion splash color!');
		});

		$form->setTitle('§l§5Make your own potion color!');
		$form->addSlider('Red', 0, 255);
		$form->addSlider('Green', 0, 255);
		$form->addSlider('Blue', 0, 255);

		$player->sendForm($form);
	}

	public function capes(Player $player) : void {
		$manager = Main::getMain()->cosmeticManager;
		$form = new SimpleForm(function (Player $player, ?string $result = null) use ($manager) : void {
			if(is_null($result)) return;
			switch ($result){
				case 'reset':
					$manager->setCape($player, '');
					$player->sendMessage(TF::RED . 'Your cape has been reset.');
					break;
				default:
					$c = $manager->createCape($player, $result);
					if($c){
						$player->sendMessage(TF::GREEN . 'Your cape has been set to ' . TF::YELLOW . "$result!");
						SessionManager::getInstance()->getSession($player)->cape = $result;
					} else {
						$player->sendMessage(TF::RED . 'That cape is currently unavailable.');
					}
			}
		});

		$form->setTitle('§l§bCapes!');
		$form->setContent('Equip a cape.');
		$form->addButton(TF::RED . 'Remove Your Cape!', label: 'reset');
		foreach(array_keys($manager->capes) as $cape) $form->addButton($cape, label: $cape);

		$player->sendForm($form);
	}

	public function partyForm(Player $player) : void {
		$session = SessionManager::getInstance()->getSession($player);
		$party = $session->getParty();
		$form = new SimpleForm(function (Player $player, $result = null) use ($party, $session){
			if(is_null($result)) return;
			switch ($result){
				case "create":
					if($session->hasParty()){
						$player->sendMessage(TF::RED . "You are already in a party!");
						return;
					}
					Main::getMain()->partyManager->createParty($player);
					break;
				case "invites":
					$this->invitesForm($player);
					break;
				case "duel":
					if(count($party->members) < 2){
						$player->sendMessage(TF::RED . "You need 2 or more players to start a party duel!");
						return;
					}
					$this->partyDuelForm($player);
					break;
				case "spectate":
					if($party->hasMatch()){
						$player->teleport($party->getMatch()->getArena()->getSpawn1());
						$player->setGamemode(GameMode::SPECTATOR());
						$player->sendMessage(TF::GRAY . "You are now " . TF::LIGHT_PURPLE . "spectating " . TF::GRAY . "your party's match!");
						$party->sendMessage($player->getName() . " is now spectating the party duel!");
						$party->getMatch()->spectators[$player->getName()] = true;
					} else {
						$player->sendMessage(TF::RED . "That party does not have a valid match!");
					}
					break;
				case "invite":
					$this->playerListForm($player);
					break;
				case "disband":
					if($party->hasMatch()){
						$player->sendMessage(TF::RED . "You can't disband your party in the middle of a match! Transfer ownership through the Members form.");
						return;
					}
					$this->confirmDisband($player, $party);
					break;
				case "members":
					$this->membersForm($player, $party, "view");
					break;
				case "leave":
					if(!$party instanceof Party){
						$player->sendMessage(TF::RED . "The party you are in no longer exists!");
						return;
					}
					if($party->isLeader($player)){
						$player->sendMessage(TF::RED . "You cannot leave your own party! You must disband it.");
						return;
					}
					$this->confirmLeave($player);
					break;
			}
		});

		if(!$session->hasParty()){
			$form->addButton("Create", -1, "", "create");
			$form->addButton("Invites", -1, "", "invites");
		}

		if($session->hasParty()){
			$party = $session->getParty();
			if($party->isLeader($player)){
				$form->addButton("Duel", -1, "", "duel");
				$form->addButton("Invite", -1, "", "invite");
				$form->addButton("Members", -1, "", "members");
				$form->addButton("Disband", -1, "", "disband");
			} else {
				$form->addButton("Members", -1, "", "members");
				$form->addButton("Leave", -1, "", "leave");
			}
			if($party->hasMatch()){
				$form->addButton("Spectate", -1, "", "spectate");
			}
		}

		$form->setTitle(TF::BOLD . TF::LIGHT_PURPLE . "Party!");

		$player->sendForm($form);
	}

	public function invitesForm(Player $player) : void {
		$invites = [];
		foreach(Main::getMain()->partyManager->getInvites($player) as $invite){
			if($invite instanceof PartyInvite) $invites[] = $invite;
		}

		$form = new SimpleForm(function (Player $player, $result = null) use ($invites){
			if(is_null($result)) return;
			switch ($result){
				case "back":
					$this->partyForm($player);
					break;
				default:
					$session = SessionManager::getInstance()->getSession($player);
					if($session->hasParty()){
						$player->sendMessage(TF::RED . "You are already in a party!");
						return;
					}
					$this->manageInviteForm($player, $invites[$result]);
					break;
			}
		});

		$form->setTitle("Invites");
		$c = count($invites);
		for($i = 0; $i < $c; $i++){
			if($invites[$i] instanceof PartyInvite) $form->addButton(TF::WHITE . $invites[$i]->getParty()->leader . "'s Party", -1, "", $i);
		}
		$form->addButton("Back", -1, "", "back");

		$player->sendForm($form);
	}

	public function manageInviteForm(Player $player, PartyInvite $invite) : void {
		$party = $invite->getParty();
		$form = new SimpleForm(function (Player $player, $result = null) use ($invite, $party){
			if(is_null($result)) return;
			switch ($result){
				case 0:
					if($invite != null){
						if($party->isFull()){
							$player->sendMessage(TF::RED . "That party is full!");
							return;
						}
						$invite->accept();
					} else {
						$player->sendMessage(TF::RED . "That invite is now invalid!");
					}
					break;
				case 1:
					$invite?->decline();
					break;
				case 2:
					$this->invitesForm($player);
					break;
			}
		});

		$members = implode(", ", array_keys($party->members));

		$form->setTitle("Manage Invite");
		$form->setContent(
			TF::GRAY . "Party invite from " . TF::LIGHT_PURPLE . $invite->sender . "!\n\n" .
			"Party Information:\n" . "Members: " . TF::GRAY . count($party->members) . "/" . $party->capacity . "\n" .
			TF::LIGHT_PURPLE . "Member List: " . TF::GRAY . $members
		);
		$form->addButton("Accept");
		$form->addButton("Decline");
		$form->addButton("Back");

		$player->sendForm($form);
	}

	public function confirmDisband(Player $player, Party $party) : void {
		$form = new ModalForm(function (Player $player, bool $result = null) use($party){
			if(is_null($result)) return;
			if($result){
				if($party->hasMatch()){
					$player->sendMessage(TF::RED . "You can't disband your party in the middle of a match! Transfer ownership through the Members form.");
					return;
				}
				$party->disband();
			}
		});

		$form->setTitle("Confirm Disband!");
		$form->setContent(TF::RED . "Are you sure you would like to disband your party?");
		$form->setButton1(TF::GREEN . "Yes");
		$form->setButton2(TF::RED . "No");

		$player->sendForm($form);
	}

	public function playerListForm(Player $player) : void {

		$players = [];
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			$players[] = $p->getName();
		}

		$pm = Main::getMain()->partyManager;
		$s = SessionManager::getInstance()->getSession($player);
		$party = $s->getParty();

		$form = new CustomForm(function (Player $player, array $data = null) use($players, $pm, $party){
			if(is_null($data)){
				return;
			}

			$name = $players[$data[1]];
			$p2 = Server::getInstance()->getPlayerExact($name);
			if(count($party->members) >= $party->capacity){
				$player->sendMessage(TF::RED . "Your party capacity is full! Kick a member or buy a rank to increase this!");
				return;
			}
			if(is_null($p2)){
				$player->sendMessage(TF::RED . "That player is offline!");
				return;
			}
			if(!$p2->spawned){
				$player->sendMessage(TF::RED . "That player has not finished the login process! Try again in a few seconds.");
				return;
			}
			if($p2->getXuid() === $player->getXuid()){
				$player->sendMessage(TF::RED . "You cannot invite yourself!");
				return;
			}

			if($pm->hasInvite($p2, $party)){
				$player->sendMessage(TF::RED . "That player already has a pending invite to your party!");
				return;
			}

			$s2 = SessionManager::getInstance()->getSession($p2);

			if($s2->hasParty()){
				$player->sendMessage(TF::RED . "That player is already in a party!");
				$p2->sendMessage(TF::LIGHT_PURPLE . $player->getName() . " tried to invite you to their party but you are already in a party!");
				return;
			}
			$pm->invitePlayer($party, $player, $p2);
		});


		$form->setTitle("Invite a Player!");
		$form->addLabel(TF::LIGHT_PURPLE . count($s->getParty()->members) . "/" . $s->getParty()->capacity . " Members");
		$form->addDropdown("Select a Player", $players);

		$player->sendForm($form);
	}

	public function membersForm(Player $player, Party $party, string $type) : void {
		$form = new SimpleForm(function (Player $player, $result = null) use ($type){
			if(is_null($result)) return;
			switch ($result){
				case "back":
					$this->partyForm($player);
					break;
				default:
					$this->memberForm($player, $result);
			}
		});

		$form->setTitle("Member List");
		foreach($party->members as $member => $rank){
			$form->addButton(TF::LIGHT_PURPLE . $rank . TF::GRAY . " $member", -1, "", $member);
		}
		$form->addButton("Back", -1, "", "back");

		$player->sendForm($form);
	}

	public function memberForm(Player $player, $data) : void {
		$p = Server::getInstance()->getPlayerExact($data);
		$form = new SimpleForm(function (Player $player, $result = null) use($p){
			if(is_null($result)) return;
			switch($result){
				case "back":
					$this->partyForm($player);
					break;
				case "kick":
					if($p instanceof Player){
						$this->confirmKickMember($player, $p);
					} else {
						$player->sendMessage(TF::RED . "That player is no longer online! They have been automatically removed from the party.");
					}
					break;
				case "promote":
					$this->confirmPromote($player, $p);
			}
		});

		$form->setTitle("Member Info!");
		if($p instanceof Player){
			$session = SessionManager::getInstance()->getSession($p);
			$party = $session->getParty();
			if($party instanceof Party){
				$session = SessionManager::getInstance()->getSession($p);
				$form->setContent(
					TF::LIGHT_PURPLE . "Name " . TF::WHITE . $p->getName() . "\n" .
					TF::LIGHT_PURPLE . "Party Rank: " . TF::WHITE . $party->members[$p->getName()] . "\n" .
					TF::LIGHT_PURPLE . "Kills " . TF::WHITE . "$session->kills\n" .
					TF::LIGHT_PURPLE . "Deaths " . TF::WHITE . "$session->deaths\n" .
					TF::LIGHT_PURPLE . "KDR " . TF::WHITE . "{$session->getKillToDeathRatio()}\n"
				);
				if($party->isLeader($player) && !$party->isLeader($p)) {
					$form->addButton(TF::RED . "Kick", -1, "", "kick");
					$form->addButton("Set Leader", -1, "", "promote");
				}
			} else {
				$form->setContent(TF::RED . "That player is no longer in this party!");
			}
		} else {
			$form->setContent(TF::RED . "That player was not found! They must be online to view their info.");
		}
		$form->addButton("Back", -1, "", "back");

		$player->sendForm($form);
	}

	public function confirmPromote(Player $player, Player $p) : void {
		$form = new ModalForm(function (Player $player, bool $result = null) use ($p){
			if(is_null($result)) return;
			if($result) {
				$session = SessionManager::getInstance()->getSession($player);
				$party = $session->getParty();
				if($party->hasMember($p)) {
					$party->setLeader($p, "promotion");
				} else {
					$player->sendMessage(TF::RED . "That player is no longer in the party!");
				}
			}
		});

		$form->setTitle("Confirm Promotion!");
		$form->setContent(TF::GRAY . "Are you sure you would like to set " . TF::LIGHT_PURPLE . $p->getName() . " as the party leader?\n" . TF::RED . "Warning: You will no longer be the party leader by doing this.");
		$form->setButton1(TF::GREEN . "Yes");
		$form->setButton2(TF::RED . "No");

		$player->sendForm($form);
	}

	public function confirmKickMember(Player $player, Player $p) : void {
		$form = new ModalForm(function (Player $player, bool $result = null) use ($p){
			if(is_null($result)) return;
			if($result) {
				$session = SessionManager::getInstance()->getSession($player);
				$party = $session->getParty();
				if($party->hasMember($p)) {
					$party->kickMember($p);
				} else {
					$player->sendMessage(TF::RED . "That player is no longer in the party!");
				}
			}
		});

		$form->setTitle("Confirm Kick!");
		$form->setContent(TF::GRAY . "Are you sure you would like to kick " . TF::LIGHT_PURPLE . $p->getName() . "?");
		$form->setButton1(TF::GREEN . "Yes");
		$form->setButton2(TF::RED . "No");

		$player->sendForm($form);
	}

	public function confirmLeave(Player $player) : void {
		$form = new ModalForm(function (Player $player, bool $result = null){
			if(is_null($result)) return;
			if($result) {
				$session = SessionManager::getInstance()->getSession($player);
				$party = $session->getParty();
				if($party instanceof Party){
					if(!$party->hasMember($player)){
						$player->sendMessage(TF::RED . "You are no longer in the party!");
						return;
					}
					if($party->isLeader($player)){
						$player->sendMessage(TF::RED . "You cannot leave your own party! You must disband it.");
						return;
					}
					if($session->hasMatch()){
						$match = $session->getMatch();
						$match->removePlayer($player);
					}
					$party->removeMember($player);
				}
			}
		});

		$form->setTitle("Confirm Leave!");
		$form->setContent(TF::GRAY . "Are you sure you want to leave your party?");
		$form->setButton1(TF::GREEN . "Yes");
		$form->setButton2(TF::RED . "No");

		$player->sendForm($form);
	}

	public function partyDuelForm(Player $player) : void {

		$form = new CustomForm(function (Player $player, array $data = null) : void {
			if(is_null($data)){
				return;
			}
			$arena = Main::getMain()->arenaManager->getArenaByMode(Translator::PARTY_MODES[$data[1]]);
			if(is_null($arena)){
				$player->sendMessage(TF::DARK_RED . "All {Translator::PARTY_MODES[$data[1]]} arenas are currently full! Please try again or request more arenas on discord.");
				return;
			}
			$party = SessionManager::getInstance()->getSession($player)->getParty();
			Main::getMain()->matchManager->startPartyMatch($party, Translator::PARTY_MODES[$data[1]]);
		});

		$form->setTitle(TF::LIGHT_PURPLE . TF::BOLD . "Party Duels!");
		$form->addDropdown("Select a type", ['Duel'], 0);
		$form->addDropdown("Select a mode", Translator::PARTY_MODES, 0);

		$player->sendForm($form);
	}

	public function emoteForm(Player $player) : void {
		$session = SessionManager::getInstance()->getSession($player);

		$form = new SimpleForm(function (Player $player, $data = null) : void {
			if(is_null($data)) return;
			$player->sendMessage(TF::GREEN . 'Now broadcasting the emote ' . TF::YELLOW . Translator::EMOTES[$data] . '!');
			$player->getServer()->broadcastPackets($player->getServer()->getOnlinePlayers(), [EmotePacket::create($player->getId(), $data, EmotePacket::FLAG_SERVER)]);
		});

		$count = $session->unlockedEmotes;
		$form->setTitle(TF::GOLD . 'Emotes! ' . TF::GREEN . $count . TF::GRAY . '/' . TF::GOLD . count(Translator::EMOTES));
		$i = 0;
		foreach(Translator::EMOTES as $uuid => $name){
			$i++;
			$form->addButton(($i <= $count ? TF::GREEN : TF::RED) . $name, label: $uuid);
		}

		$player->sendForm($form);
	}

}