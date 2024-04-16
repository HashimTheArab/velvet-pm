<?php

namespace Prim\Velvet;

use pocketmine\block\BaseSign;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\network\mcpe\protocol\types\PlayerMovementType;
use pocketmine\network\PacketHandlingException;
use Prim\Velvet\Anticheat\NetworkStackLatencyManager;
use Prim\Velvet\Bots\Bot;
use Prim\Velvet\Bots\BotMatch;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Items\EnderPearl as PearlItem;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\InputMode;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\XpCollectSound;
use Prim\Velvet\Duels\Parties\PartyMatch;
use Prim\Velvet\Managers\EntityManager;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Tasks\AsyncAltCheckTask;
use Prim\Velvet\Tasks\BleedTask;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\Utils\Utils;
use function array_keys;
use function array_rand;
use function count;
use function in_array;
use function is_infinite;
use function is_nan;
use function is_null;
use function mt_rand;
use function round;
use function str_contains;
use function str_pad;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;
use function time;
use function trim;
use const STR_PAD_BOTH;

class EventListener implements Listener {

	public Main $main;

	public function __construct(Main $main){
		$this->main = $main;
	}

	public function onCreation(PlayerCreationEvent $event) : void {
		$event->setPlayerClass(VelvetPlayer::class);
	}

	/**
	 * @param PlayerPreLoginEvent $event
	 * @handleCancelled
	 * @priority LOWEST
	 */
	public function onPreLogin(PlayerPreLoginEvent $event) : void {
		$name = $event->getPlayerInfo()->getUsername();
		if($event->isKickReasonSet(PlayerPreLoginEvent::KICK_REASON_BANNED)){
			$ban = $this->main->getServer()->getNameBans()->getEntry($name);
			if(is_null($ban->getExpires())){
				$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_BANNED, TF::BOLD . TF::DARK_PURPLE . str_pad('Velvet', strlen(Translator::BLACKLISTED_MESSAGE), pad_type: STR_PAD_BOTH) . TF::RESET . "\n" . TF::RED . Translator::BLACKLISTED_MESSAGE);
			} else {
				$fm = Utils::timeToStringDifference($ban->getExpires());
				$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_BANNED,
					TF::BOLD . TF::DARK_PURPLE . str_pad('Velvet', strlen("Moderator » {$ban->getSource()}"), pad_type: STR_PAD_BOTH) . TF::RESET .
					TF::DARK_PURPLE . "\nModerator » " . TF::LIGHT_PURPLE . "{$ban->getSource()}\n" .
					TF::DARK_PURPLE . 'Reason » ' . TF::LIGHT_PURPLE . "{$ban->getReason()}\n" .
					TF::DARK_PURPLE . 'Expires » ' . TF::LIGHT_PURPLE . "$fm\n" .
					TF::DARK_PURPLE . 'Discord » ' . TF::LIGHT_PURPLE . Translator::DISCORD_LINK
				);
			}
			return;
		}
		$titleId = $this->main->titleIds[$event->getIp() . ':' . $event->getPort()] ?? null;
		if($titleId !== null){
			$deviceOS = $event->getPlayerInfo()->getExtraData()['DeviceOS'];
			$expectedOS = match($titleId){
				'1739947436' => DeviceOS::ANDROID,
				'1810924247' => DeviceOS::IOS,
				'1944307183' => DeviceOS::AMAZON,
				'896928775' => DeviceOS::WINDOWS_10,
				'2044456598' => DeviceOS::PLAYSTATION,
				//'2047319603' => DeviceOS::NINTENDO,
				'1828326430' => DeviceOS::XBOX,
				default => null
			};
			if(is_null($expectedOS)){
				$webhook = new Webhook(Translator::TITLEID_WEBHOOK);
				$msg = new Message();
				$embed = new Embed();
				$embed->setTitle('New Title ID');
				$embed->setDescription("Name: $name\nTitle ID: $titleId\nDevice OS: " . Translator::SYSTEMS[$deviceOS]);
				$msg->addEmbed($embed);
				$webhook->send($msg);
			} else {
				if($expectedOS !== $deviceOS){
					$event->setKickReason(PlayerPreLoginEvent::KICK_REASON_BANNED, TF::AQUA . "Device Spoofer Detected\n" . TF::RED . str_pad('You are banned.', strlen('Device Spoofer Detected.'), pad_type: STR_PAD_BOTH));
					Utils::autoBan($name, 'Device Spoofer', ['Real OS' => Translator::SYSTEMS[$expectedOS], 'Given OS' => Translator::SYSTEMS[$deviceOS]]);
				}
			}
			unset($this->main->titleIds[$event->getIp() . ':' . $event->getPort()]);
		}
	}

	public function onLogin(PlayerLoginEvent $event) : void {
		/** @var VelvetPlayer $player */
		$player = $event->getPlayer();
		Server::getInstance()->getAsyncPool()->submitTask(AsyncAltCheckTask::create($player));
		if(!$this->main->isRegistered($player)) $this->main->registerPlayer($player);
		$this->main->sessionManager->createSession($player);

		$data = $player->getNetworkSession()->getPlayerInfo()->getExtraData();
		$player->deviceID = $data['DeviceId'];
		$player->deviceOS = $data['DeviceOS'];
		$player->inputMode = $data['CurrentInputMode'];
		$player->deviceModel = $data['DeviceModel'];
		if(in_array($player->deviceOS, Translator::MOBILE)) $player->setFlag(Flags::MOBILE);
		if($player->inputMode === InputMode::TOUCHSCREEN) $player->setFlag(Flags::TOUCH);
		$player->teleport($this->main->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());

		/*
		$db = $this->plugin->db;
		if(!$this->plugin->isRegistered($player)) $db->registerPlayer($player);
		if(!$db->currentIGNIsSet($player)) $db->addIGN($player);
		$this->plugin->getSessionManager()->createSession($player);

		$data = $this->plugin->getData($player);
		if($data["name"] !== $player->getName()) $this->plugin->db->setNewIGN($player);
		foreach($data["igns"] as $ign){
			if($bans->isBanned($ign) && $ign != $name) $this->plugin->autoBanChangedIGN($player, $ign, $event);
		}
		 */
	}

	public function onJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		$name = $player->getName();
		$event->setJoinMessage(TF::GREEN . TF::BOLD . "✔ $name");
		SessionManager::getInstance()->getSession($player)->onJoin();
	}

	public function onQuit(PlayerQuitEvent $event) : void {
		$player = $event->getPlayer();
		$name = $player->getName();

		$event->setQuitMessage(TF::BOLD . TF::DARK_RED . "✘ $name");
		if($this->main->isTagged($name) && $event->getQuitReason() === 'client disconnect') $player->kill();
		$identifier = $player->getNetworkSession()->getIp() . ':' . $player->getNetworkSession()->getPort();
		if(isset($this->main->titleIds[$identifier])){
			unset($this->main->titleIds[$identifier]);
		}
		SessionManager::getInstance()->getSession($player)?->onQuit();
	}

	public function onDeath(PlayerDeathEvent $event) : void {
		$player = $event->getPlayer();

		$session = $this->main->sessionManager->getSession($player);
		if($session->hasMatch()){
			$match = $session->getMatch();
			$match->removePlayer($player);
		}

		$game = $this->main->game;
		if($game !== null){
			if($game->isFighting($player) && count($game->fighting) > 1) $game->removePlayer($player, null, true);
		}

		$level = $player->getWorld();
		$cause = $player->getLastDamageCause();
		$name = $player->getDisplayName();
		$streak = $session->killstreak;

		$event->setXpDropAmount(0);
		$event->setDeathMessage('');
		if($this->main->isTagged($player->getName())) $this->main->setTagged($player->getName(), false);

		if($cause instanceof EntityDamageByEntityEvent){
			$d = $cause->getDamager();
			if($d instanceof Player && $d->getName() !== $player->getName()){
				$hp = round($d->getHealth(), 2);
				$dname = $d->getDisplayName();
				$msg = Translator::DEATH_MESSAGES[array_rand(Translator::DEATH_MESSAGES)];
				$d->setHealth(20);
				$dSession = $this->main->sessionManager->getSession($d);
				if(!$player instanceof Bot && $level->getFolderName() !== Translator::OITC_WORLD){
					$dSession->addKills();
					$dSession->addKillstreak();
					$d->sendMessage(TF::GREEN . "You are on a killstreak of $dSession->killstreak!");
					if($dSession->killstreak > $dSession->topKillstreak) $dSession->addTopKillstreak();
				}

				if(!$d instanceof Bot && $d->getWorld()->getFolderName() !== Translator::OITC_WORLD){
					$session->addDeaths();
					if($streak !== 0){
						$player->sendMessage(TF::RED . "You lost your killstreak of $streak!");
						$session->setKillstreak(0);
					}
				}

				switch($level->getFolderName()){
					case Translator::NODEBUFF_WORLD:
						$dpots = count($d->getInventory()->all(ItemFactory::getInstance()->get(438, 22)));
						$pots = count($player->getInventory()->all(ItemFactory::getInstance()->get(438, 22)));
						$event->setDeathMessage("§3$name §7[$pots Pots] §3was $msg by $dname §7[§c$hp" . "§7] [$dpots Pots]");
						break;
					case Translator::GAPPLE_WORLD:
						$gc = 0;
						$gd = 0;
						foreach($player->getInventory()->getContents() as $content) if($content->getId() === 322) $gc = $content->getCount();
						foreach($d->getInventory()->getContents() as $content) if($content->getId() === 322) $gd = $content->getCount();
						$event->setDeathMessage("§3$name §7[$gc Gaps] §3was $msg by $dname §7[§c$hp" . "§7] [$gd Gaps]");
						$level->addParticle($player->getPosition(), new ExplodeParticle(), [$d]);
						$level->addSound($player->getPosition(), new ExplodeSound());
						break;
					case Translator::DIAMOND_WORLD:
						$level->addParticle($player->getPosition(), new ExplodeParticle(), [$d]);
						$level->addSound($player->getPosition(), new ExplodeSound());
						break;
					case Translator::OITC_WORLD:
						$d->getInventory()->addItem(VanillaItems::ARROW());
						$d->sendMessage(TF::YELLOW . 'You killed ' . TF::LIGHT_PURPLE . $name . '!');
						$event->setDeathMessage('');
						break;
					case Translator::DUELS_WORLD:
					case Translator::GOD_DUELS_WORLD:
					case Translator::BOT_DUELS_WORLD:
						$event->setDeathMessage('');
						break;
					default:
						$event->setDeathMessage("§3$name was $msg by $dname §7[§c$hp" . '§7]');
				}

				$matchedLevel = match($level->getFolderName()){
					Translator::NODEBUFF_WORLD => 'nodebuff',
					Translator::GAPPLE_WORLD => 'gapple',
					Translator::BUILDUHC_WORLD => 'build',
					Translator::DIAMOND_WORLD => 'diamond',
					Translator::GOD_WORLD => 'god',
					default => null
				};
				if($matchedLevel !== null){
					if($this->main->isTagged($d->getName()) && $d->getWorld()->getFolderName() === $level->getFolderName()){
						$this->main->kits->$matchedLevel($d);
					}
				}
			}
		}
	}

	public function onTeleport(EntityTeleportEvent $event) : void {
		$player = $event->getEntity();
		if($player instanceof Player && $event->getTo()->getWorld()->getFolderName() !== $event->getFrom()->getWorld()->getFolderName()){
			$qm = $this->main->queueManager;
			if($qm->inQueue($player)){
				$qm->removePlayer($player);
				$player->sendMessage(TF::RED . 'You have been removed from the queue!');
			}

			if($player->spawned){
				$session = $this->main->sessionManager->getSession($player);
				if($session->hasMatch()){
					$match = $session->getMatch();
					if($match->started && !$match->ended){
						if($match->type === 'party'){
							/** @var PartyMatch $match */
							if(isset($match->alive[$player->getName()])){
								$match->removePlayer($player, true);
							}
						} else {
							$match->removePlayer($player);
						}
					}
				}
			}

			$target = $event->getTo()->getWorld()->getFolderName();

			if($player->getHealth() < 20) $player->setHealth(20);
			if($player->getScale() != 1) $player->setScale(1);
			if($player->getXpManager()->getXpProgress() > 0) $player->getXpManager()->setXpProgress(0);
			if($player->getGamemode()->equals(GameMode::SPECTATOR()) && $player->spawned) $player->setGamemode(GameMode::SURVIVAL());

			foreach($player->getEffects()->all() as $effect){
				if($effect->getType() === VanillaEffects::NIGHT_VISION()) continue;
				$player->getEffects()->remove($effect->getType());
			}

			$transferLevel = match($target){
				Translator::LOBBY_WORLD => 'worlditems',
				Translator::NODEBUFF_WORLD => 'nodebuff',
				Translator::GAPPLE_WORLD => 'gapple',
				Translator::BUILDUHC_WORLD => 'build',
				Translator::DIAMOND_WORLD => 'diamond',
				Translator::GOD_WORLD => 'god',
				Translator::OITC_WORLD => 'oitc',
				default => null
			};
			if(is_null($transferLevel)){
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
			} else {
				$this->main->kits->{$transferLevel}($player);
			}
			if($target === Translator::LOBBY_WORLD){
				EntityManager::getInstance()->floatingText?->showAll($player);
			} else {
				EntityManager::getInstance()->floatingText?->hideAll($player);
			}

			if($event->getFrom()->getWorld()->getFolderName() === Translator::EVENTS_WORLD){
				$game = $this->main->game;
				if($game !== null){
					if($game->isSpectating($player)){
						$game->removeSpectator($player);
					} elseif($game->isFighting($player)){
						$game->removePlayer($player, null, true);
					}
					$player->setGamemode(GameMode::SURVIVAL());
				}
			}
		}
	}

	public function onHit(ProjectileHitEntityEvent $event) : void {
		$entity = $event->getEntity();
		$player = $entity->getOwningEntity();
		if($entity instanceof Arrow){
			$damagedPlayer = $event->getEntityHit();
			$damagerWorldId = $damagedPlayer->getWorld()->getId();
			if($damagedPlayer instanceof Player){
				if($player instanceof VelvetPlayer && !$player->hasFlag(Flags::VANISHED)){
					$dName = $damagedPlayer->getName();
					$name = $player->getName();
					$playerWorldId = $player->getWorld()->getId();
					if($damagerWorldId === $playerWorldId){
						$player->broadcastSound(new XpCollectSound());
						if($player->getWorld()->getFolderName() === Translator::OITC_WORLD){
							$player->getInventory()->addItem(VanillaItems::ARROW());
							if($damagedPlayer->getLastDamageCause() instanceof EntityDamageByEntityEvent && $name !== $dName) $player->sendMessage(TF::YELLOW . "You killed " . TF::LIGHT_PURPLE . "$dName!");
						}
					}
					if($damagedPlayer->getWorld()->getFolderName() === Translator::OITC_WORLD && $name !== $dName) {
						$damagedPlayer->kill();
						$damagedPlayer->sendMessage(TF::GRAY . 'You were killed by ' . TF::LIGHT_PURPLE . "$name!");
					}
				}
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 * @priority LOWEST
	 */
	public function onDamage(EntityDamageEvent $event) : void {
		$player = $event->getEntity();
		if ($player instanceof Player) {
			if($event->getCause() === EntityDamageEvent::CAUSE_VOID){
				$event->cancel();

				$session = $this->main->sessionManager->getSession($player);
				if($session->hasMatch()){
					$match = $session->getMatch();
					$match->removePlayer($player);
				}

				$game = $this->main->game;
				if($game !== null){
					$cause = $player->getLastDamageCause();
					if($game->isFighting($player)){
						if($cause instanceof EntityDamageByEntityEvent){ // player was knocked off
							$damager = $cause->getDamager();
							if($damager instanceof Player){
								$fighting = $game->isFighting($damager);
								$game->removePlayer($player, $fighting ? $damager : null, !$fighting);
							}
						} else { // player walked off
							$game->removePlayer($player, null, true);
						}
					} elseif($game->inEvent($player) || $game->isSpectating($player)){
						$player->teleport($game->spawn);
					} else {
						$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
					}
				} else {
					$player->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
				}
			}

			$levelName = $player->getWorld()->getFolderName();
			switch($levelName) {
				case Translator::LOBBY_WORLD:
					$event->cancel();
					break;
				case Translator::GAPPLE_WORLD:
					if((new AxisAlignedBB(321, 96, 174, 335, 106, 188))->isVectorInside($player->getPosition())){
						$event->cancel();
					}
					break;
				case Translator::NODEBUFF_WORLD:
					if($player->getPosition()->y >= 123) $event->cancel();
					break;
				case Translator::GOD_WORLD:
					if($this->main->isInGodSpawn($player)) $event->cancel();
					break;
				case Translator::DIAMOND_WORLD:
					if((new AxisAlignedBB(294, 83, 282, 299, 91, 290))->isVectorInside($player->getPosition())){
						$event->cancel();
					}
					break;
				case Translator::EVENTS_WORLD:
					$game = $this->main->game;
					if($game !== null && !$game->isFighting($player)) $event->cancel();
					break;
			}

			if($levelName !== Translator::BUILDUHC_WORLD && $event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0.0){
				$event->cancel();
			}

			/** @var VelvetPlayer $player */
			if($player->hasFlag(Flags::VANISHED)) $event->cancel();
		}
	}

	public function onPlayerBurn(EntityCombustEvent $event) : void {
		$player = $event->getEntity();
		if($player instanceof VelvetPlayer && $player->hasFlag(Flags::VANISHED)) $event->cancel();
	}

	public function onEntityDeath(EntityDeathEvent $event) : void {
		$event->setDrops([]);
		$entity = $event->getEntity();
		if($entity instanceof Bot){
			$player = $entity->getTargetEntity();
			if($player instanceof Player){
				$session = $this->main->sessionManager->getSession($player);
				if($session->hasMatch()){
					$match = $session->getMatch();
					if($match->type === BotMatch::TYPE) $match->removePlayer($player);
				}
			}
		}
	}

	public function onDrop(PlayerDropItemEvent $event) : void {
		if($event->getPlayer()->getWorld()->getFolderName() !== Translator::BUILDUHC_WORLD) $event->cancel();
	}

	public function onBreak(BlockBreakEvent $event) : void {
		$player = $event->getPlayer();
		/** @var $player VelvetPlayer */
		if($this->main->isInBuild($player, $event->getBlock()->getPosition())){
			$event->setDrops([]);
		} elseif(!$player->hasFlag(Flags::BUILDING)) $event->cancel();
	}

	public function onPlace(BlockPlaceEvent $event) : void {
		$player = $event->getPlayer();
		/** @var $player VelvetPlayer */
		if(!$player->hasFlag(Flags::BUILDING) && !$this->main->isInBuild($player, $event->getBlock()->getPosition())){
			$event->cancel();
		}
	}

	public function PickUp(EntityItemPickupEvent $event) : void {
		$inv = $event->getInventory();
		if($inv instanceof PlayerInventory){
			/** @var $holder VelvetPlayer */
			$holder = $inv->getHolder();
			if($holder->hasFlag(Flags::VANISHED)) $event->cancel();
		}
	}

	public function onChat(PlayerChatEvent $event) : void {
		$message = $event->getMessage();
		/** @var VelvetPlayer $player */
		$player = $event->getPlayer();
		$name = $player->getName();
		$session = $this->main->sessionManager->getSession($player);
		if($session->rank === 'Custom' && $session->customRankChat === ''){
			$player->sendMessage(TF::RED . 'You have not set your chat format! ' . TF::GRAY . 'To do so, use the Profile head at spawn.');
			$event->cancel();
			return;
		}
		if($message[0] === '*'){
			if($session->hasParty()){
				$event->cancel();
				$message = str_replace('*', null, $message);
				$party = $session->getParty();
				$rank = $party->members[$name];
				$party->sendMessage("[$rank] {$player->getDisplayName()}: $message");
			}
		} elseif($message[0] === ';' && $player->hasFlag(Flags::STAFF)){
			$event->cancel();
			$message = str_replace(';', null, $message);
			/** @var VelvetPlayer $p */
			foreach($this->main->getServer()->getOnlinePlayers() as $p){
				if($p->hasFlag(Flags::STAFF)) $p->sendMessage(TF::GREEN . "[Staff] $name: " . TF::DARK_GREEN . $message);
			}
		}
		if(str_contains($message, 'pot') && str_contains($message, 'lag')){
			$message = 'im gay and i like furries';
		}
		$event->setFormat($player->hasFlag(Flags::NICKED) ? Utils::getDefaultChatFormat($player, $message) : PermissionManager::getInstance()->getRank($session->rank)->getChatFormat($player, $message));

		if(!$player->hasPermission('antispam.false')){
			if(time() - $player->chatCooldown < Translator::CHAT_COOLDOWN) {
				$event->cancel();
				$cdt = Translator::CHAT_COOLDOWN - (time() - $player->chatCooldown);
				$player->sendMessage(TF::RED . 'You are on cooldown for ' . TF::AQUA . ($cdt === 1 ? $cdt . TF::RED . ' more second!' : $cdt . TF::RED . ' more seconds!') . TF::AQUA . ' Get rid of this with a rank at velvet.tebex.io!');
			} else {
				$player->chatCooldown = time();
			}
		}
	}

	public function onCommandPreProcess(PlayerCommandPreprocessEvent $event) : void {
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		if($msg[0] === '/'){
			Server::getInstance()->getLogger()->info(TF::LIGHT_PURPLE . 'COMMAND: <' . $player->getName() . '> ' . TF::clean($msg));
			if($this->main->isTagged($player->getName())){
				if(in_array(trim(substr($msg, 1)), Translator::BANNED_COMMANDS)) {
					$event->cancel();
					$player->sendMessage(TF::RED . 'You are in combat!');
				}
			}
		}
	}

	public function onHunger(PlayerExhaustEvent $event) : void {
		$event->cancel();
	}

	public function onTransaction(InventoryTransactionEvent $event) : void {
		if($event->getTransaction()->getSource()->getWorld()->getFolderName() === Translator::LOBBY_WORLD) $event->cancel();
	}

	public function onConsume(PlayerItemConsumeEvent $event){
		$item = $event->getItem();
		if($item->getId() === ItemIds::GOLDEN_APPLE) {
			/** @var VelvetPlayer $player */
			$player = $event->getPlayer();
			if(time() - $player->gappleCooldown < Translator::GAPPLE_COOLDOWN){
				$event->cancel();
				$cdt = Translator::GAPPLE_COOLDOWN - (time() - $player->gappleCooldown);
				$player->sendMessage(TF::GOLD . 'You have ' . ($cdt === 1 ? "$cdt second" : "$cdt seconds") . ' left before you can eat another golden apple!');
			} else {
				$player->gappleCooldown = time();
			}
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * @priority LOW
	 */
	public function entityDamage(EntityDamageByEntityEvent $event) : void {
		$player = $event->getEntity();
		$level = $player->getWorld()->getFolderName();

		if(in_array($level, Translator::NODEBUFF_KB)){
			$event->setKnockBack(0.395);
		}

		if ($level === Translator::GOD_WORLD || $level === Translator::GOD_DUELS_WORLD) {
			if($level === Translator::GOD_WORLD && $this->main->isInGodSpawn($player)) return;
			if ($player instanceof VelvetPlayer) {
				$damager = $event->getDamager();
				$enchants = $this->main->enchants;
				if ($damager instanceof Player) {
					$bruh = mt_rand(1, 35); //default 45
					switch ($bruh) {
						case 1:
							$enchants->kaboom($player, $damager);
							break;
						case 2:
							$enchants->lightning($player);
							$pp = mt_rand(0, 150);
							if ($pp == 3) {
								$enchants->kaboom($player, $damager);
							}
							break;
						case 3:
							if(!$player->hasFlag(Flags::BLEEDING)){
								$player->setFlag(Flags::BLEEDING);
								$this->main->getScheduler()->scheduleRepeatingTask(new BleedTask($this->main, $player), 60);
							}
							break;
						case 4:
							$enchants->poison($player);
							break;
					}
				}
			}
		}

		$attacker = $event->getDamager();
		if($player instanceof Player){
			if($attacker instanceof Player){
				if($level === Translator::EVENTS_WORLD){
					$game = $this->main->game;
					if($game !== null && !$game->isFighting($player) && !$game->isFighting($attacker)) $event->cancel();
				}
				foreach([$player, $attacker] as $p) {
					if(!$this->main->isTagged($p)) $p->sendMessage('§6You are now in combat!');
					$this->main->setTagged($p);
				}
			}
			if($attacker instanceof Bot){
				if(!$this->main->isTagged($player->getName())) {
					$player->sendMessage('§6You are now in combat!');
				}
				$this->main->setTagged($player->getName());
			}
		} elseif($player instanceof Bot){
			if($attacker instanceof Player && !$this->main->isTagged($attacker->getName())) {
				$attacker->sendMessage('§6You are now in combat!');
			}
			$this->main->setTagged($attacker->getName());
		}

	}

	public function onUse(PlayerItemUseEvent $event) : void {
		$item = $event->getItem();
		/** @var VelvetPlayer $player */
		$player = $event->getPlayer();
		if($item instanceof PearlItem){
			if (time() - $player->pearlCooldown < Translator::PEARL_COOLDOWN) {
				$event->cancel();
				$cdt = Translator::PEARL_COOLDOWN - (time() - $player->pearlCooldown);
				$player->sendMessage(TF::GOLD . 'You have ' . ($cdt === 1 ? "$cdt second" : "$cdt seconds") . ' left before you can use an enderpearl again!');
			} else {
				$player->pearlCooldown = time();
			}
		} else {
			if($player->getWorld()->getFolderName() === Translator::LOBBY_WORLD){
				$this->doForms($player, $item);
				if(!$player->hasFlag(Flags::BUILDING)) $event->cancel();
			}
		}
	}

	public function onInteract(PlayerInteractEvent $event) : void {
		/** @var VelvetPlayer $player */
		$player = $event->getPlayer();
		$item = $event->getItem();

		if ($item->getId() === 438 && $item->getMeta() === 22 && $player->hasFlag(Flags::MOBILE) && $player->hasFlag(Flags::TOUCH)) {
			$event->cancel();
			$item->onClickAir($player, $player->getDirectionVector());
			$player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
		}
		if($player->getWorld()->getFolderName() === Translator::EVENTS_WORLD){
			$sign = $event->getBlock();
			if($sign instanceof BaseSign && isset(Translator::TELEPORTATIONS[strtolower(TF::clean($sign->getText()->getLine(0)))])){
				$game = $this->main->game;
				if($game !== null && !$game->isFighting($player)){
					$l = Translator::TELEPORTATIONS[strtolower(TF::clean($sign->getText()->getLine(0)))];
					$player->teleport(new Vector3($l[0], $l[1], $l[2]));
				}
			}
		} elseif($player->getWorld()->getFolderName() === Translator::LOBBY_WORLD){
			if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK){
				$this->doForms($player, $item);
				if(!$player->hasFlag(Flags::BUILDING)) $event->cancel();
			} elseif($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
				if(!$player->hasFlag(Flags::BUILDING)) $event->cancel();
			}
		}
	}

	public function doForms(Player $player, Item $item) : void {
		$queue = $this->main->queueManager->inQueue($player);
		$party = $this->main->sessionManager->getSession($player)->hasParty();

		if(in_array($item->getName(), array_keys(Translator::FORMS))){
			switch($item->getName()){
				case '§3Duels!':
					if($party){
						$player->sendMessage(Translator::CANNOT_USE_PARTY);
						return;
					}
					break;
				case '§dParty!':
					if($queue){
						$player->sendMessage(Translator::CANNOT_USE_QUEUED);
						return;
					}
					break;
				default:
					if($queue || $party){
						$player->sendMessage(Translator::CANNOT_USE_QUEUED_OR_PARTY);
						return;
					}
			}
			$this->main->forms->{Translator::FORMS[$item->getName()]}($player);
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 * @handleCancelled
	 */
	public function onReceive(DataPacketReceiveEvent $event) : void {
		/** @var VelvetPlayer|null $player */
		$player = $event->getOrigin()->getPlayer();
		if($player !== null && $player->spawned){
			$pk = $event->getPacket();
			$session = SessionManager::getInstance()->getSession($player);
			switch($pk::NETWORK_ID){
				case LevelSoundEventPacket::NETWORK_ID:
					/** @var LevelSoundEventPacket $pk */
					if($pk->sound === LevelSoundEvent::ATTACK_NODAMAGE){
						$this->main->registerCPS($player);
						$event->cancel();
						$player = $event->getOrigin()->getPlayer();
						$viewers = $player->getViewers();
						$viewers[] = $player;
						$player->getServer()->broadcastPackets($viewers, [$pk]);
					}
					if($pk->sound === LevelSoundEvent::ATTACK_STRONG){
						$this->main->registerCPS($player);
					}
					break;
				case EmotePacket::NETWORK_ID:
					/** @var EmotePacket $pk */
					$player->getServer()->broadcastPackets($player->getViewers(), [EmotePacket::create($player->getId(), $pk->getEmoteId(), EmotePacket::FLAG_SERVER)]);
					break;
				case AnimatePacket::NETWORK_ID:
					/** @var AnimatePacket $pk */
					if($pk->action === AnimatePacket::ACTION_SWING_ARM){
						$event->cancel();
						$player->getServer()->broadcastPackets($player->getViewers(), [$pk]);
					}
					break;
				case MovePlayerPacket::NETWORK_ID:
					/** @var MovePlayerPacket $pk */
					if(is_null($session->acData->lastLocation)){
						$session->acData->lastLocation = $pk->position->subtract(0, 1.62, 0);
						$session->acData->currentMotion = null;
						return;
					}
					break;
				case RespawnPacket::NETWORK_ID:
					/** @var RespawnPacket $pk */
					$session->acData->fullySpawned = $pk->respawnState === RespawnPacket::CLIENT_READY_TO_SPAWN;
					break;
				case NetworkStackLatencyPacket::NETWORK_ID:
					/** @var NetworkStackLatencyPacket $pk */
					NetworkStackLatencyManager::execute($session, $pk->timestamp);
					break;
				case PlayerAuthInputPacket::NETWORK_ID:
					/** @var PlayerAuthInputPacket $pk */
					$event->cancel();
					$pkPos = $pk->getPosition();
					foreach([$pkPos->x, $pkPos->y, $pkPos->z, $pk->getYaw(), $pk->getHeadYaw(), $pk->getPitch()] as $float){
						if(is_infinite($float) || is_nan($float)){
							Server::getInstance()->getLogger()->debug("Invalid movement received, contains NAN/INF components");
							break;
						}
					}

					$networkSession = $event->getOrigin();
					$pos = $player->getLocation();
					$distanceSquared = $pkPos->round(4)->subtract(0, 1.62, 0)->distanceSquared($player->getPosition());
					// The packet is sent every tick so only handle movement if the player has moved
					if($pk->getYaw() - $pos->getYaw() !== 0.0 || $pk->getPitch() - $pos->getPitch() !== 0.0 || $distanceSquared !== 0.0){
						$networkSession->getHandler()->handleMovePlayer(MovePlayerPacket::simple($player->getId(), $pkPos, $pk->getPitch(), $pk->getYaw(), $pk->getHeadYaw(), MovePlayerPacket::MODE_NORMAL, false, 0, $pk->getTick()));
					}

					if($pk->getItemInteractionData() !== null){
						$data = $pk->getItemInteractionData();
						$networkSession->getHandler()->handleInventoryTransaction(InventoryTransactionPacket::create($data->getRequestId(), $data->getRequestChangedSlots(), $data->getTransactionData()));
					}
					if($pk->getBlockActions() !== null){
						foreach($pk->getBlockActions() as $blockAction){
							$actionType = match($blockAction->getActionType()){ // these are the same but PM doesn't account for them
								PlayerAction::CONTINUE_DESTROY_BLOCK => PlayerAction::START_BREAK,
								PlayerAction::PREDICT_DESTROY_BLOCK => PlayerAction::STOP_BREAK,
								default => $blockAction->getActionType()
							};
							if($blockAction instanceof PlayerBlockActionWithBlockInfo){
								$networkSession->getHandler()->handlePlayerAction(PlayerActionPacket::create($player->getId(), $actionType, $blockAction->getBlockPosition(), $blockAction->getFace()));
							}
						}
					}

					if($pk->hasFlag(PlayerAuthInputFlags::START_SPRINTING)){
						if(!$player->toggleSprint(true)){
							$player->sendData([$player]);
						}
					}
					if($pk->hasFlag(PlayerAuthInputFlags::STOP_SPRINTING)){
						if(!$player->toggleSprint(false)){
							$player->sendData([$player]);
						}
					}
					if($pk->hasFlag(PlayerAuthInputFlags::START_SNEAKING)){
						if(!$player->toggleSneak(true)){
							$player->sendData([$player]);
						}
					}
					if($pk->hasFlag(PlayerAuthInputFlags::STOP_SNEAKING)){
						if(!$player->toggleSneak(false)){
							$player->sendData([$player]);
						}
					}
					$player->inputMode = $pk->getInputMode();
					$session->acData->handlePacketData($player, $pk);
					break;
			}
			$checks = $this->main->antiCheatManager->getChecks($pk);
			if($checks !== null){
				foreach($checks as $check) $check->run($pk, $session);
			}
		} elseif($event->getPacket()::NETWORK_ID === LoginPacket::NETWORK_ID){
			/** @var LoginPacket $pk */
			$pk = $event->getPacket();
			foreach ($pk->chainDataJwt->chain as $jwt) {
				try {
					[, $claims,] = JwtUtils::parse($jwt);
				} catch (JwtException $e) {
					throw PacketHandlingException::wrap($e);
				}
				if (isset($claims['extraData']['titleId'])) {
					$this->main->titleIds[$event->getOrigin()->getIp() . ':' . $event->getOrigin()->getPort()] = $claims['extraData']['titleId'];
				}
			}
		}
	}

	// skidded from ethan thx!
	public function onSend(DataPacketSendEvent $event) : void {
		foreach($event->getTargets() as $target){
			/** @var VelvetPlayer|null $player */
			$player = $target->getPlayer();
			foreach($event->getPackets() as $pk){
				if($pk instanceof StartGamePacket){
					$pk->playerMovementSettings = new PlayerMovementSettings(PlayerMovementType::SERVER_AUTHORITATIVE_V2_REWIND, 20, false);
				} elseif($pk instanceof SetActorMotionPacket && $player !== null && $player->spawned){
					$session = SessionManager::getInstance()->getSession($player);
					if($pk->actorRuntimeId === $player->getId()){
						$motion = $pk->motion;
						NetworkStackLatencyManager::send($session, fn() => ($session->acData->currentMotion = $motion));
					}
				}
			}
		}
	}

}