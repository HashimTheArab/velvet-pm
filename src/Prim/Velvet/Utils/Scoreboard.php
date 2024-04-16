<?php

namespace Prim\Velvet\Utils;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use Prim\Velvet\Duels\Parties\PartyMatch;
use Prim\Velvet\Sessions\Session;
use function count;

class Scoreboard {

	public static self $instance;
	public const NAME = 'Velvet';

	const NORMAL = 0;
	const MATCH = 1;
	const BOT_MATCH = 2;
	const PARTY_MATCH = 3;

	const TYPES = ['sendNormalScoreboard', 'sendDuelScoreboard', 'sendBotDuelScoreboard', 'sendPartyDuelScoreboard'];

	public int $onlinePlayers = 0;
	public int $tps = 20;

	public function new(Player $player) : void {
		$pk = new SetDisplayObjectivePacket();
		$pk->displaySlot = 'sidebar';
		$pk->objectiveName = self::NAME;
		$pk->displayName = '§l§dVelvet§5Practice';
		$pk->criteriaName = 'dummy';
		$pk->sortOrder = 1;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

    public function remove(Player $player){
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = self::NAME;
        $player->getNetworkSession()->sendDataPacket($pk);
    }

	public function update(Player $player, array $lines) : void {
		$entries = [];
		foreach($lines as $score => $text){
			$entry = new ScorePacketEntry();
			$entry->objectiveName = self::NAME;
			$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
			$entry->customName = $text;
			$entry->score = $score;
			$entry->scoreboardId = $score;

			$entries[] = $entry;
		}

		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_REMOVE;
		$pk->entries = $entries;
		$player->getNetworkSession()->sendDataPacket($pk);

		$pk = new SetScorePacket();
		$pk->type = SetScorePacket::TYPE_CHANGE;
		$pk->entries = $entries;
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function sendNormalScoreboard(Session $session) : void {
		$this->update($session->owner, [
			6 => "§dName §b{$session->owner->getDisplayName()}",
			5 => "§dOnline §b$this->onlinePlayers",
			4 => "§dK §b$session->kills §dD §b$session->deaths",
			3 => "§dKDR §b{$session->getKillToDeathRatio()}",
			2 => "§dPing §b{$session->owner->getNetworkSession()->getPing()} §dTPS §b$this->tps",
			1 => '§d' . Translator::SERVER_ADDRESS,
		]);
	}

	public function sendDuelScoreboard(Session $session) : void {
		$match = $session->getMatch();
		$player = $session->owner;
		$op = $match->getOpponent($player);
		$this->update($session->owner, [
			8 => "§dType: §bDuel",
			7 => "§dMode: §b{$match->getMode()}",
			6 => "§dOpponent: §b{$op->getDisplayName()}",
			5 => '      ',
			4 => "§dYour Ping: §b{$player->getNetworkSession()->getPing()}",
			3 => "§dTheir Ping: §b{$op->getNetworkSession()->getPing()}",
			2 => '      ',
			1 => '§d' . Translator::SERVER_ADDRESS,
		]);
	}

	public function sendPartyDuelScoreboard(Session $session) : void {
		/** @var PartyMatch $match */
		$match = $session->getMatch();
		$max = count($match->players);
		$left = count($match->alive);
		$this->update($session->owner, [
			5 => '§dType: §bParty Duel',
			4 => "§dMode: §b{$match->getMode()}",
			3 => "§dPlayers: §b$left/$max",
			2 => "§dPing: §b{$session->owner->getNetworkSession()->getPing()}",
			1 => '§d' . Translator::SERVER_ADDRESS
		]);
	}

	public function sendScoreboard(Session $session) : void {
		$this->{self::TYPES[$session->owner->scoreboard]}($session);
	}

	public static function getInstance() : self {
		return self::$instance;
	}

}