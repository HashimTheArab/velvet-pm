<?php

namespace Prim\Velvet\Utils;

use DateTime;
use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\VelvetPlayer;
use function ceil;
use function count;
use function floor;
use function is_null;
use function mb_scrub;
use function preg_match_all;
use function preg_replace;
use function str_replace;
use function time;
use function trim;

class Utils {

	public static function stringToTime(string $string) : ?DateTime {
		if(trim($string) === '') return null;

		$t = new DateTime();

		preg_match_all('/[0-9]+(y|mo|w|d|h|m|s)|[0-9]+/', $string, $found);

		if(count($found[0]) < 1 || count($found[1]) < 1) return null;
		$m = match($found[1][0]){
			'y' => 'year',
			'mo' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'm' => 'minute',
			's' => 'second',
			default => null
		};
		if(is_null($m)) return null;
		$t->modify('+' . preg_replace('/[^0-9]/', '', $found[0][0]) . " $m");
		return $t;
	}

	public static function timeToStringDifference(DateTime $time) : string {
		$remainingTime = $time->getTimestamp() - time();
		$d = floor($remainingTime / 86400);
		$hourSeconds = $remainingTime % 86400;
		$h = floor($hourSeconds / 3600);
		$minuteSec = $hourSeconds % 3600;
		$m = floor($minuteSec / 60);
		$s = ceil($minuteSec % 60);

		$string = '';
		if($d > 0) $string .= ($d == 1 ? "$d Day " : "$d Days ");
		if($h > 0) $string .= ($h == 1 ? "$h Hour " : "$h Hours ");
		if($m > 0) $string .= ($m == 1 ? "$m Minute " : "$m Minutes ");
		if($s > 0) $string .= ($s == 1 ? "$h Second " : "$h Seconds ");
		return $string;
	}

	public static function applyDefaultTags(Player $player, string $chatFormat, ?string $message) : string {
		if (is_null($message)) $message = '';
		return str_replace(['{display_name}', '{msg}', '{kills}', '>', '{prefix}'], [
			$player->getDisplayName(), PermissionManager::getInstance()->stripChatColors($message),
			SessionManager::getInstance()->getSession($player)->kills, '»', SessionManager::getInstance()->getSession($player)->tag
		], $chatFormat);
	}

	public static function getDefaultChatFormat(Player $player, string $message) : string {
		return self::applyDefaultTags($player, TF::colorize(Translator::DEFAULT_RANK_CHAT), $message);
	}

	public static function getDefaultNameTag(Player $player) : string {
		return self::applyDefaultTags($player, TF::colorize(Translator::DEFAULT_RANK_NAMETAG), null);
	}

	public static function whisperToStaff(string $message) : void {
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_TRANSLATION;
		$pk->message = $message;
		/** @var VelvetPlayer $p */
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			if($p->hasFlag(Flags::STAFF)) $p->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public static function clean(string $string, bool $removeFormat = true) : string{
		$string = mb_scrub($string, 'UTF-8');
		$string = self::preg_replace("/[\x{E000}-\x{F8FF}]/u", $string); //remove unicode private-use-area characters (they might break the console)
		if($removeFormat){
			$string = str_replace(TF::ESCAPE, "", self::preg_replace("/" . TF::ESCAPE . "[0-9a-gl-or]/u", $string));
		}
		return str_replace("\x1b", "", self::preg_replace("/\x1b[\\(\\][[0-9;\\[\\(]+[Bm]/u", $string));
	}

	private static function preg_replace(string $pattern, string $string) : string{
		$result = preg_replace($pattern, '', $string);
		if(is_null($result)) throw new InvalidArgumentException('bad clean idk');
		return $result;
	}

	public static function banPlayer(string $target, string $mod, string $reason, string $length, string $extraData, bool $sendWebhook = true) : void {
		$player = Server::getInstance()->getPlayerExact($target);
		if($player !== null) $target = $player->getName();
		$banlist = Server::getInstance()->getNameBans();
		if($banlist->isBanned($target)) return;

		$time = self::stringToTime($length);
		$timeString = self::timeToStringDifference($time);

		if($sendWebhook){
			$webhook = new Webhook(Translator::AUTOBAN_WEBHOOK);
			$msg = new Message;
			$embed = new Embed;
			$embed->setTitle("$target has been automatically banned!");
			$embed->setDescription("Player: $target\nModerator: $mod\nLength: $timeString\nReason: $reason\n$extraData");
			$msg->addEmbed($embed);
			$webhook->send($msg);
		}

		$player?->kick(TF::RED . 'You were banned by ' . TF::AQUA . $mod . TF::EOL . TF::RED . 'Reason: ' . TF::AQUA . $reason . TF::EOL . TF::RED . 'Length: ' . TF::AQUA . $timeString, false);
		$banlist->addBan($target, $reason, $time, $mod);
		Server::getInstance()->broadcastMessage(TF::RED . $target . TF::AQUA . " was banned by " . TF::GREEN . $mod . TF::EOL . TF::RED . "Reason: " . TF::AQUA . $reason . TF::EOL . TF::RED . "Length: " . TF::AQUA . $timeString);
	}

	public static function autoBan(string $target, string $reason, array $extraData = [], bool $sendWebhook = true) : void {
		$data = '';
		foreach($extraData as $key => $value){
			$data .= "$key: $value\n";
		}
		self::banPlayer($target, 'VelvetAI', $reason, '30d', $data, $sendWebhook);
	}

}