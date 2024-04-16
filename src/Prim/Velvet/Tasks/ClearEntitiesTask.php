<?php

namespace Prim\Velvet\Tasks;

use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use Prim\Velvet\Utils\Translator;
use function array_rand;

class ClearEntitiesTask extends Task {

	private Server $server;

	public function __construct(){
		$this->server = Server::getInstance();
	}

	public function onRun() : void {
		foreach($this->server->getWorldManager()->getWorlds() as $elevel) {
			foreach ($elevel->getEntities() as $entity) {
				if ($entity instanceof ItemEntity || $entity instanceof ExperienceOrb || $entity instanceof Arrow) {
					$entity->flagForDespawn();
				}
			}
		}
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TRANSLATION;
		$pk->message = Translator::VELVET_PREFIX . Translator::MESSAGES[array_rand(Translator::MESSAGES)];
		foreach($this->server->getOnlinePlayers() as $p){
			$p->getNetworkSession()->sendDataPacket($pk);
		}
	}

}