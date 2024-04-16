<?php

namespace Prim\Velvet\Permissions;

use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionManager as PManager;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use RuntimeException;
use function array_diff;
use function array_merge;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_dir;
use function is_null;
use function json_decode;
use function json_encode;
use function mkdir;
use function preg_match;
use function str_replace;
use function strtolower;
use function substr;
use function yaml_emit_file;
use function yaml_parse_file;

class PermissionManager {

	public static self $instance;

	const NODE_RANK = 'group';
	const NODE_PERMISSIONS = 'permissions';
	const NODE_TAG = 'prefix';
	const NOT_FOUND = null;
	const INVALID_NAME = -1;
	const ALREADY_EXISTS = 0;
	const SUCCESS = 1;

	public string $dataFolder = '';
	public string $rankDataFolder = '';

	public bool $isRanksLoaded = false;
	public array $attachments = [];
	public array $ranks = [];

	public function __construct(){
		self::$instance = $this;
		$this->dataFolder = Main::getMain()->getDataFolder() . 'players/';
		$this->rankDataFolder = Main::getMain()->getDataFolder() . 'ranks.json';
		if(!is_dir($this->dataFolder)) mkdir($this->dataFolder, 0777, true);
		$this->updateRanks();
	}

	public function getPlayerRank(IPlayer $player) : Rank {
		$rankName = $this->getNode($player, self::NODE_RANK);
		$rank = $this->getRank($rankName);
		if(is_null($rank)) {
			Server::getInstance()->getLogger()->critical('Invalid rank name found in ' . $player->getName() . "'s player data");
			Server::getInstance()->getLogger()->critical("Restoring the rank data to 'default'");
			$defaultRank = $this->getDefaultRank();
			$this->setRank($player, $defaultRank);
			return $defaultRank;
		}
		return $rank;
	}

	public function getNode(IPlayer $player, string $node){
		$userData = $this->getPlayerData($player);
		return $userData[$node] ?? null;
	}

	public function getUserPermissions(IPlayer $player) : array {
		$permissions = $this->getNode($player, self::NODE_PERMISSIONS);
		if(!is_array($permissions)) {
			Server::getInstance()->getLogger()->critical("Invalid 'permissions' node given to " . __METHOD__ . '()');
			return [];
		}
		return $permissions;
	}

	public function setData(IPlayer $player, array $data) : void {
		$this->setPlayerData($player, $data);
	}

	public function setRank(IPlayer $player, Rank $rank) : void {
		$this->setNode($player, self::NODE_RANK, $rank->getName());
		$this->updatePermissions($player);
	}

	public function setNode(IPlayer $player, string $node, ?string $value) : void {
		$tempUserData = $this->getPlayerData($player);
		$tempUserData[$node] = $value;
		$this->setData($player, $tempUserData);
	}

	public function setPermission(IPlayer $player, string $permission) : void {
		$tempUserData = $this->getPlayerData($player);
		$tempUserData["permissions"][] = $permission;
		$this->setData($player, $tempUserData);
		$this->updatePermissions($player);
	}

	public function unsetPermission(IPlayer $player, string $permission) : void {
		$tempUserData = $this->getPlayerData($player);
		if(!in_array($permission, $tempUserData['permissions'])) return;
		$tempUserData["permissions"] = array_diff($tempUserData['permissions'], [$permission]);
		$this->setData($player, $tempUserData);
		$this->updatePermissions($player);
	}

	public function getRankData(Rank $rank) : array {
		$rankName = $rank->getName();
		if(!isset($this->getRanksData()[$rankName]) || !is_array($this->getRanksData()[$rankName])) return [];
		return $this->getRanksData()[$rankName];
	}

	public function getRanksData() : array {
		return json_decode(file_get_contents($this->rankDataFolder), true);
	}

	public function getPlayerData(IPlayer $player, bool $onUpdate = false) : array {
		$name = $player->getName();

		if($onUpdate) {
			if(!file_exists($this->dataFolder . strtolower($name) . '.yml')) {
				return ['userName' => $name, 'group' => $this->getDefaultRank()->getName(), 'permissions' => []];
			}
			return yaml_parse_file($this->dataFolder . strtolower($name) . '.yml');
		} else {
			if(file_exists($this->dataFolder . strtolower($name) . ".yml")) {
				return yaml_parse_file($this->dataFolder . strtolower($name) . '.yml');
			}
			return ['userName' => $name, 'group' => $this->getDefaultRank()->getName(), 'permissions' => []];
		}
	}

	public function setRankData(Rank $rank, array $tempRankData){
		$data = json_decode(file_get_contents($this->rankDataFolder), true);
		$data[$rank->getName()] = $tempRankData;
		file_put_contents($this->rankDataFolder, json_encode($data, JSON_PRETTY_PRINT));
	}

	public function setRanksData(array $tempRanksData){
		file_put_contents($this->rankDataFolder, json_encode($tempRanksData, JSON_PRETTY_PRINT));
	}

	public function setPlayerData(IPlayer $player, array $tempPlayerData){
		yaml_emit_file($this->dataFolder . strtolower($player->getName()) . '.yml', $tempPlayerData);
	}

	public function addRank(string $rankName) : int {
		$ranksData = $this->getRanksData();

		if(!$this->isValidRankName($rankName)) return self::INVALID_NAME;
		if(isset($ranksData[$rankName])) return self::ALREADY_EXISTS;

		$ranksData[$rankName] = [
			'isDefault' => 'false',
			'inheritance' => [],
			'permissions' => [],
			'chat' => "§a[{kills}] §8[$rankName] §r{prefix}§a{display_name} §b> §7{msg}",
			'nametag' => "§8[$rankName] §7{display_name}"
		];

		$this->setRanksData($ranksData);
		$this->updateRanks();
		return self::SUCCESS;
	}

	public function getAttachment(Player $player) : ?PermissionAttachment {
		if(!isset($this->attachments[$player->getName()])) throw new RuntimeException("Tried to calculate permissions on {$player->getName()} using null attachment");
		return $this->attachments[$player->getName()];
	}

	public function getDefaultRank() : ?Rank {
		$defaultRanks = [];

		foreach($this->getRanks() as $rank) {
			if($rank->isDefault()) $defaultRanks[] = $rank;
		}

		if(count($defaultRanks) === 1) return $defaultRanks[0];

		if(count($defaultRanks) > 1) {
			Server::getInstance()->getLogger()->warning('More than one default rank was declared in the ranks file.');
		} elseif(count($defaultRanks) <= 0) {
			Server::getInstance()->getLogger()->warning('No default rank was found in the ranks file.');
		}

		Server::getInstance()->getLogger()->info("Setting the default rank automatically.");

		foreach($this->getRanks() as $tempRank) {
			if(count($tempRank->getParentRanks()) === 0) {
				$this->setDefaultRank($tempRank);
				return $tempRank;
			}
		}

		return null;
	}

	public function getRank(string $rankName) : ?Rank {
		if(!isset($this->ranks[$rankName])) {
			/** @var Rank $rank */
			foreach($this->ranks as $rank) {
				if($rank->getAlias() === $rankName) return $rank;
			}
			Server::getInstance()->getLogger()->debug("Rank $rankName was not found.");
			return null;
		}

		/** @var Rank $rank */
		$rank = $this->ranks[$rankName];
		if(empty($rank->getData())) {
			Server::getInstance()->getLogger()->warning("Group $rankName has invalid or corrupted data.");
			return null;
		}

		return $rank;
	}

	/**
	 * @return Rank[]
	 */
	public function getRanks() : array {
		if(!$this->isRanksLoaded) throw new RuntimeException("No ranks loaded, maybe a provider error?");
		return $this->ranks;
	}

	public function getPermissions(IPlayer $player) : array {
		$rank = $this->getPlayerRank($player);
		if($player instanceof VelvetPlayer){
			if(in_array($rank->getName(), Translator::STAFF_RANKS)){
				$player->setFlag(Flags::STAFF);
			} elseif($player->hasFlag(Flags::STAFF)) $player->setFlag(Flags::STAFF);
			SessionManager::getInstance()->getSession($player)?->setRank($rank);
		}
		return array_merge($rank->getPermissions(), $this->getUserPermissions($player));
	}

	public function getPlayer(string $name) : IPlayer {
		$player = Server::getInstance()->getPlayerByPrefix($name);
		return $player instanceof Player ? $player : Server::getInstance()->getOfflinePlayer($name);
	}

	public function isValidRankName(string $rankName) : int {
		return preg_match('/[0-9a-zA-Z\xA1-\xFE]$/', $rankName);
	}

	public function registerPlayer(Player $player) : void {
		Server::getInstance()->getLogger()->debug("Registering player {$player->getName()}...");

		if(!isset($this->attachments[$player->getName()])) {
			$attachment = $player->addAttachment(Main::getMain());
			$this->attachments[$player->getName()] = $attachment;
			$this->updatePermissions($player);
		}
	}

	public function removeRank(string $rankName) : ?int {
		if(!$this->isValidRankName($rankName)) return self::INVALID_NAME;

		$ranksData = $this->getRanksData();
		if(!isset($ranksData[$rankName])) return self::NOT_FOUND;

		unset($ranksData[$rankName]);
		$this->setRanksData($ranksData);
		$this->updateRanks();

		$manager = SessionManager::getInstance();
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			$session = $manager->getSession($p);
			if($session->rank === $rankName) $session->setRank($this->getRank(Translator::DEFAULT_RANK));
		}
		return self::SUCCESS;
	}

	public function setDefaultRank(Rank $rank) : void {
		foreach($this->getRanks() as $currentRank) {
			$isDefault = $currentRank->getNode('isDefault');
			if($isDefault) $currentRank->removeNode('isDefault');
		}

		$rank->setDefault();
	}

	public function sortRankData(){
		foreach($this->getRanks() as $rank) {
			$rank->sortPermissions();
		}
	}

	public function updateRanks(){
		$this->ranks = [];

		foreach($this->getRanksData() as $name => $data) {
			$this->ranks[$name] = new Rank($name, $data);
		}

		if(empty($this->ranks)) throw new RuntimeException('No ranks were found!');
		$this->isRanksLoaded = true;
		$this->sortRankData();
	}

	public function updatePermissions(IPlayer $player){
		if(!$player instanceof Player) return;
		$permissions = [];
		foreach($this->getPermissions($player) as $permission) {
			if($permission === '*') {
				foreach(PManager::getInstance()->getPermissions() as $tmp) {
					$permissions[$tmp->getName()] = true;
				}
			} else {
				$isNegative = str_starts_with($permission, '-');
				if($isNegative) $permission = substr($permission, 1);
				$permissions[$permission] = !$isNegative;
			}
		}

		/** @var PermissionAttachment $attachment */
		$attachment = $this->getAttachment($player);
		$attachment->clearPermissions();
		$attachment->setPermissions($permissions);
	}

	public function updatePlayersInRank(Rank $rank){
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			if($this->getPlayerRank($player) === $rank) $this->updatePermissions($player);
		}
	}

	public function unregisterPlayer(Player $player){
		Server::getInstance()->getLogger()->debug("Unregistering player {$player->getName()}...");
		if(isset($this->attachments[$player->getName()])) $player->removeAttachment($this->attachments[$player->getName()]);
		unset($this->attachments[$player->getName()]);
	}

	public function setTag(IPlayer $player, ?string $tag){
		$this->setNode($player, self::NODE_TAG, $tag);
	}

	public static function getInstance() : self {
		return self::$instance;
	}

	public function stripChatColors(string $string) : array|string {
		$string = str_replace(TF::BLACK, '', $string);
		$string = str_replace(TF::DARK_BLUE, '', $string);
		$string = str_replace(TF::DARK_GREEN, '', $string);
		$string = str_replace(TF::DARK_AQUA, '', $string);
		$string = str_replace(TF::DARK_RED, '', $string);
		$string = str_replace(TF::DARK_PURPLE, '', $string);
		$string = str_replace(TF::GOLD, '', $string);
		$string = str_replace('§g', '', $string);
		$string = str_replace(TF::GRAY, '', $string);
		$string = str_replace(TF::DARK_GRAY, '', $string);
		$string = str_replace(TF::BLUE, '', $string);
		$string = str_replace(TF::GREEN, '', $string);
		$string = str_replace(TF::AQUA, '', $string);
		$string = str_replace(TF::RED, '', $string);
		$string = str_replace(TF::LIGHT_PURPLE, '', $string);
		$string = str_replace(TF::YELLOW, '', $string);
		$string = str_replace(TF::WHITE, '', $string);
		$string = str_replace(TF::OBFUSCATED, '', $string);
		$string = str_replace(TF::BOLD, '', $string);
		$string = str_replace(TF::STRIKETHROUGH, '', $string);
		$string = str_replace(TF::UNDERLINE, '', $string);
		$string = str_replace(TF::ITALIC, '', $string);
		return str_replace(TF::RESET, '', $string);
	}

}