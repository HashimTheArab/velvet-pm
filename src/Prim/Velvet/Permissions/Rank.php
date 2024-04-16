<?php

namespace Prim\Velvet\Permissions;

use pocketmine\player\Player;
use pocketmine\Server;
use Prim\Velvet\Sessions\SessionManager;
use pocketmine\utils\TextFormat as TF;
use function array_diff;
use function array_merge;
use function array_unique;
use function in_array;
use function is_array;
use function is_null;
use function sort;
use function str_replace;

class Rank {

	public string $name = '';
	public array $parents = [];
	public array $data = [];

	public function __construct(string $name, array $data){
		$this->name = $name;
		$this->data = $data;
	}

	public function __toString() : string {
		return $this->name;
	}

	public function getAlias(){
		return $this->getNode('alias') ?? $this->name;
	}

	public function getData() : array {
		return PermissionManager::getInstance()->getRankData($this);
	}

	public function getPermissions() : array {
		$permissions = $this->getNode(PermissionManager::NODE_PERMISSIONS);

		if(!is_array($permissions)) {
			Server::getInstance()->getLogger()->critical("Invalid 'permissions' node given to " .  __METHOD__);
			return [];
		}

		foreach($this->getParentRanks() as $parentRank) {
			$parentPermissions = $parentRank->getPermissions();
			if(is_null($parentPermissions)) $parentPermissions = [];
			$permissions = array_merge($parentPermissions, $permissions);
		}

		return $permissions;
	}

	public function getName() : string {
		return $this->name;
	}

	public function getNode($node){
		return $this->getData()[$node] ?? null;
	}

	/**
	 * @return Rank[]
	 */
	public function getParentRanks() : array {
		if($this->parents === []) {
			if(!is_array($this->getNode('inheritance'))) {
				Server::getInstance()->getLogger()->critical("Invalid 'inheritance' node given to " . __METHOD__);
				return [];
			}
			foreach($this->getNode('inheritance') as $parentRankName) {
				$parentRank = PermissionManager::getInstance()->getRank($parentRankName);
				if($parentRank !== null) $this->parents[] = $parentRank;
			}
		}

		return $this->parents;
	}

	public function isDefault() : bool {
		return $this->getNode('isDefault') === 'true';
	}

	public function removeNode(string $node) : void {
		$tempRankData = $this->getData();

		if(isset($tempRankData[$node])) {
			unset($tempRankData[$node]);
			$this->setData($tempRankData);
		}
	}

	public function setData(array $data) : void {
		PermissionManager::getInstance()->setRankData($this, $data);
	}

	public function setDefault() : void {
		$this->setNode('isDefault', 'true');
	}

	public function addPermission(string $permission) : bool {
		$tempRankData = $this->getData();
		$tempRankData['permissions'][] = $permission;
		$this->setData($tempRankData);
		PermissionManager::getInstance()->updatePlayersInRank($this);
		return true;
	}

	public function setNode(string $node, string $value) : void {
		$tempRankData = $this->getData();
		$tempRankData[$node] = $value;
		$this->setData($tempRankData);
	}

	public function sortPermissions() : void {
		$tempRankData = $this->getData();
		if(isset($tempRankData['permissions'])) {
			$tempRankData['permissions'] = array_unique($tempRankData['permissions']);
			sort($tempRankData['permissions']);
		}
		$this->setData($tempRankData);
	}

	public function unsetPermission(string $permission) : bool {
		$tempRankData = $this->getData();
		if(!in_array($permission, $tempRankData['permissions'])) return false;
		$tempRankData['permissions'] = array_diff($tempRankData['permissions'], [$permission]);
		$this->setData($tempRankData);
		PermissionManager::getInstance()->updatePlayersInRank($this);
		return true;
	}

	public function applyTags(Player $player, string $chatFormat, ?string $message) : string {
		$chatFormat = str_replace('{display_name}', $player->getDisplayName(), $chatFormat);
		if (is_null($message)) $message = '';

		if (!$player->getServer()->isOp($player->getName())) {
			$chatFormat = str_replace('{msg}', PermissionManager::getInstance()->stripChatColors($message), $chatFormat);
		} else {
			$chatFormat = str_replace('{msg}', $message, $chatFormat);
		}

		$chatFormat = str_replace('{kills}', SessionManager::getInstance()->getSession($player)->kills, $chatFormat);
		$chatFormat = str_replace('{deaths}', SessionManager::getInstance()->getSession($player)->deaths, $chatFormat);
		$chatFormat = str_replace('>', '»', $chatFormat);
		return str_replace('{prefix}', SessionManager::getInstance()->getSession($player)->tag, $chatFormat);
	}

	public function getChatFormat(Player $player, string $message) : string {
		if($this->name === 'Custom'){
			return $this->applyTags($player, TF::colorize(SessionManager::getInstance()->getSession($player)->customRankChat), $message);
		}
		return $this->applyTags($player, TF::colorize($this->data['chat']), $message);
	}

	public function getNameTag(Player $player) : string {
		if($this->name === 'Custom'){
			return $this->applyTags($player, TF::colorize(SessionManager::getInstance()->getSession($player)->customRankNameTag), null);
		}
		return $this->applyTags($player, TF::colorize($this->data['nametag']), null);
	}

}