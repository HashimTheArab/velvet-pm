<?php

namespace Prim\Velvet\Anticheat\Checks;

use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Anticheat\AntiCheatManager;
use Prim\Velvet\Anticheat\FlagData;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Sessions\Session;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\Utils\Utils;
use function is_null;

abstract class Check {

	public int $id;

	public function __construct(
		public string $name,
		public string $type,
		public string $description,
		public int $punishmentType,
		public int $maxViolations
	){
		$this->id = AntiCheatManager::$instance->checkAmount++;
	}

	abstract function run(ServerboundPacket $packet, Session $session) : void;

	public function flag(Session $session, array $data) : void {
		$flagData = $session->acData->flags[$this->id] ?? null;
		if(is_null($flagData)) $flagData = $session->acData->flags[$this->id] = new FlagData();
		++$flagData->flags;

		foreach($data as $key => $value){
			$flagData->data[$key][] = $value;
		}
		$flagData->data['ping'][] = $session->owner->getNetworkSession()->getPing();

		$flags = round($flagData->flags, 2);
		if($flags >= $this->maxViolations){
			if($this->punishmentType !== AntiCheatManager::PUNISHMENT_TYPE_NONE && !Server::getInstance()->isOp($session->owner->getName())){
				$this->punish($session);
			}
		}

		$d = '';
		foreach($data as $name => $val) $d .= "$name=$val ";
		$d .= 'ping=' . $session->owner->getNetworkSession()->getPing();

		$name = $session->owner->getName();
		$p = Server::getInstance()->getOnlinePlayers();
		foreach($p as $player){
			if($player->hasPermission('anticheat.alert')){
				$player->sendMessage("§7[§4Velvet§7] §e{$name}§7 flagged §6$this->name ($this->type) §7(§cx{$flags}§7) §7[$d]");
			}
		}
	}

 	public function punish(Session $session) : void {
		$player = $session->owner;
		$type = $this->punishmentType === AntiCheatManager::PUNISHMENT_TYPE_KICK ? 'Kick' : 'Ban';
		$data = '';
		foreach($session->acData->flags as $flagData){
			$data .= $flagData->getDataString();
		}

		$webhook = new Webhook(Translator::ANTICHEAT_WEBHOOK);
		$msg = new Message;
		$embed = new Embed;
		$embed->setTitle("Anticheat $type");
		$embed->setDescription(
			"Player: {$player->getName()}\nPlayer OS: " . Translator::SYSTEMS[$player->deviceOS] . "\n" .
			"Player Input: " . Translator::CONTROLS[$player->inputMode] . "\nPlayer World: " . $player->getWorld()->getFolderName() . "\n" .
			"Online Players: " . Scoreboard::getInstance()->onlinePlayers . "\n" . $data
		);
		$msg->addEmbed($embed);
		$webhook->send($msg);
		$reason = $this->name . "($this->type)";
		if($this->punishmentType === AntiCheatManager::PUNISHMENT_TYPE_KICK){
			$player->kick(TF::RED . 'You were kicked by ' . TF::AQUA . 'VelvetAI' . TF::EOL . TF::RED . 'Reason: ' . TF::AQUA . $reason, false);
			Server::getInstance()->broadcastMessage(TF::RED . $player->getName() . TF::AQUA . " was kicked by " . TF::GREEN . 'VelvetAI' . TF::EOL . TF::RED . "Reason: " . TF::AQUA . $reason);
		} else {
			Utils::autoBan($player->getName(), $this->name . $this->type, sendWebhook: false);
		}
	}

}