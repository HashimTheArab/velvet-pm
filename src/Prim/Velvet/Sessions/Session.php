<?php

namespace Prim\Velvet\Sessions;

use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Server;
use Prim\Velvet\Anticheat\AntiCheatData;
use Prim\Velvet\Duels\NormalMatch;
use Prim\Velvet\Duels\Parties\Party;
use Prim\Velvet\Main;
use Prim\Velvet\Managers\EntityManager;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Permissions\Rank;
use Prim\Velvet\Tasks\AsyncDataLoadTask;
use Prim\Velvet\Tasks\ScoreboardTask;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\VelvetPlayer;
use function file_put_contents;
use function is_null;
use function json_encode;
use function number_format;

class Session {

	private array $invites = [];
	private ?Session $lastinviteowner = null;
	public VelvetPlayer $owner;
	private ?NormalMatch $match = null;
	private ?Party $party = null;

	public int $kills = 0;
	public int $deaths = 0;
	public int $killstreak = 0;
	public int $topKillstreak = 0;
	public int $unlockedEmotes = 3;

	public ?string $cape = null;
	public array $potColor = [];
	public ?string $tag = null;
	public string $rank = 'Normie';

	public string $nametag = '';
	public string $customRankChat = '';
	public string $customRankNameTag = '';

	public AntiCheatData $acData;

	public function __construct(VelvetPlayer $owner){
		$this->owner = $owner;
		Server::getInstance()->getAsyncPool()->submitTask(new AsyncDataLoadTask($owner->getName(), Main::getMain()->getDataPath($owner)));
	}

	public function onLogin(array $data) : void {
		$owner = $this->owner;
		$manager = PermissionManager::getInstance();
		$manager->registerPlayer($owner);
		$this->kills = $data['kills'];
		$this->deaths = $data['deaths'];
		$this->killstreak = $data['killstreak'];
		$this->topKillstreak = $data['topkillstreak'];
		$this->cape = $data['cape'] ?? null;
		if(isset($data['pot-ding']) && $data['pot-ding'] === true) $owner->setFlag(Flags::POTDING);
		$this->potColor = $data['pot-color'] ?? [248, 36, 35];
		$this->unlockedEmotes = $data['emotes'] ?? 3;
		$this->tag = $manager->getNode($owner, PermissionManager::NODE_TAG) ?? null;
		$this->rank = $manager->getPlayerRank($owner)?->getName() ?? 'Normie';
		if($this->rank === 'Custom'){
			$this->customRankChat = $data['customChat'] ?? '';
			$this->customRankNameTag = $data['customNameTag'] ?? '[Custom]§6 {display_name}';
		}

		if($this->cape !== null) Main::getMain()->cosmeticManager->createCape($owner, $this->cape);
		$this->acData = new AntiCheatData();
	}

	public function onJoin() : void {
		$main = Main::getMain();
		/** @var VelvetPlayer $player */
		$player = $this->owner;

		$main->scoretag($player);
		EntityManager::getInstance()->floatingText?->spawnAll($player);
		$this->nametag = PermissionManager::getInstance()->getRank($this->rank)->getNametag($player);
		$player->setNameTag($this->getNameTag());

		if($player->getNetworkSession()->getPing() > 70){
			$player->setImmobile();
			$main->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use($player) : void {
				$player->setImmobile(false);
			}), 25);
		}

		if($player->getGamemode() !== GameMode::SURVIVAL()) $player->setGamemode(GameMode::SURVIVAL());
		$player->getXpManager()->setXpAndProgress(0, 0);

		$player->newScoreboard();
		$main->scoreboards[$player->getName()] = $main->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($main, $player), 100);

		$player->sendTitle(TF::LIGHT_PURPLE . TF::BOLD . 'Velvet' , TF::DARK_PURPLE . 'Practice', 15, 20, 15);

		$main->kits->worlditems($player);

		$pk = new GameRulesChangedPacket();
		$pk->gameRules['doimmediaterespawn'] = new BoolGameRule(true, false);
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function onQuit() : void {
		$qm = Main::getMain()->queueManager;
		$player = $this->owner;
		PermissionManager::getInstance()->unregisterPlayer($player);
		if($this->hasMatch()) $this->match->removePlayer($player);
		if($qm->inQueue($player)) $qm->removePlayer($player);

		if($this->hasParty()){
			$party = $this->getParty();
			if($party->isLeader($player)){
				if(count($party->members) > 1){
					$party->removeMember($player);
					$p = Server::getInstance()->getPlayerExact(array_rand($party->members));
					if(!$p instanceof Player){
						for($i = 0; $i < 10; $i++){
							$p = Server::getInstance()->getPlayerExact(array_rand($party->members));
							if($p instanceof Player) break;
							if($i >= 5){
								$party->disband();
								break;
							}
						}
					}
					$party->setLeader($p);
				} else {
					$party->disband();
				}
			} else {
				$party->removeMember($player);
			}
		}

		$game = Main::getMain()->game;
		if($game !== null){
			if($game->inEvent($player)){
				if($game->isFighting($player)){
					$game->removePlayer($player, null, true);
				} else {
					$game->removePlayer($player);
				}
			} elseif($game->isSpectating($player)) $game->removeSpectator($player);
		}
		$this->save();
		SessionManager::getInstance()->closeSession($player);
	}

	public function save() : void {
		$data = Main::getMain()->getData($this->owner);
		$data['kills'] = $this->kills;
		$data['deaths'] = $this->deaths;
		$data['killstreak'] = $this->killstreak;
		$data['topkillstreak'] = $this->topKillstreak;
		$data['emotes'] = $this->unlockedEmotes;
		if(is_null($this->cape)){
			unset($data['cape']);
		} else {
			$data['cape'] = $this->cape;
		}
		if(!$this->owner->hasFlag(Flags::POTDING)){
			unset($data['pot-ding']);
		} else {
			$data['pot-ding'] = true;
		}
		if(empty($this->potColor)){
			unset($data['pot-color']);
		} else {
			$data['pot-color'] = $this->potColor;
		}
		if($this->customRankChat !== '') $data['customChat'] = $this->customRankChat;
		if($this->customRankNameTag !== '') $data['customNameTag'] = $this->customRankNameTag;
		file_put_contents(Main::getMain()->getDataPath($this->owner), json_encode($data, JSON_PRETTY_PRINT));
	}

	public function addKills(int $amount = 1) : void {
		$this->kills += $amount;
	}

	public function setKills(int $amount) : void {
		$this->kills = $amount;
	}

	public function addKillstreak(int $amount = 1) : void {
		$this->killstreak += $amount;
	}

	public function setKillstreak(int $amount) : void {
		$this->killstreak = $amount;
	}

	public function addTopKillstreak(int $amount = 1) : void {
		$this->topKillstreak += $amount;
	}

	public function addDeaths(int $amount = 1) : void {
		$this->deaths += $amount;
	}

	public function getKillToDeathRatio() : string {
		if($this->kills > 0 && $this->deaths !== 0){
			return number_format($this->kills / $this->deaths, 1);
		}
		return '0.0';
	}

	public function getOwner() : VelvetPlayer {
		return $this->owner;
	}

	public function getName() : string {
		return $this->owner->getName();
	}

	public function hasMatch() : bool {
		return $this->match != null;
	}

	public function getMatch() : ?NormalMatch {
		return $this->match;
	}

	public function setMatch(?NormalMatch $match) : void {
		$this->match = $match;
	}

	public function getLastInviteOwner() : ?Session {
		return $this->lastinviteowner;
	}

	public function getLastInviteType(Session $session){
		return $this->invites[$session->getName()];
	}

	public function setLastInviteOwner(?Session $owner){
		$this->lastinviteowner = $owner;
	}

	public function hasInviteFrom(Session $session) : bool {
		return isset($this->invites[$session->getName()]);
	}

	public function addInviteFrom(Session $session, string $mode) : void {
		$this->invites[$session->getName()] = $mode;
		$this->lastinviteowner = $session;
	}

	public function clearInvitesFrom(Session $session) : void {
		if($this->hasInviteFrom($session)){
			if($this->lastinviteowner === $session) $this->lastinviteowner = null;
			unset($this->invites[$session->getName()]);
		}
	}

	public function clearAllInvites() : void {
		$this->invites = [];
		$this->lastinviteowner = null;
	}

	public function setParty(?Party $party) : void {
		$this->party = $party;
	}

	public function removeParty() : void {
		$this->party = null;
	}

	public function hasParty() : bool {
		return $this->party !== null;
	}

	public function getParty() : ?Party {
		return $this->party;
	}

	public function setRank(Rank $rank) : void {
		$this->rank = $rank->getName();
		$this->nametag = $rank->getNameTag($this->owner);
	}

	public function getNameTag() : string {
		return $this->customRankNameTag !== '' ? $this->customRankNameTag : $this->nametag;
	}

}