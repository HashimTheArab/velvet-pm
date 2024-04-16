<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function date;
use function is_null;
use function str_pad;
use function time;

class BlacklistCommand extends Command {

	public function __construct(){
		parent::__construct(
			'blacklist',
			TF::LIGHT_PURPLE . 'Blacklist a player from the server!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/blacklist <player> <reason>'
		);
		$this->setPermission('velvet.bans.manage');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 2) {
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $args[0];
		$target = Server::getInstance()->getPlayerByPrefix($name);
		$banlist = $sender->getServer()->getNameBans();

		if($target instanceof VelvetPlayer){
			if ($target->hasFlag(Flags::STAFF) && $sender->getName() !== 'xPrim69x') {
				$sender->sendMessage(TF::DARK_RED . 'You cannot blacklist a staff member!');
				return;
			}
			$name = $target->getName();
		} else {
			if(in_array(PermissionManager::getInstance()->getPlayerRank(Server::getInstance()->getOfflinePlayer($name)), Translator::STAFF_RANKS)){
				$sender->sendMessage(TF::DARK_RED . 'You cannot blacklist a staff member!');
				return;
			}
		}

		if(Server::getInstance()->isOp($name) && $sender->getName() !== 'xPrim69x' && $sender instanceof Player){
			$sender->sendMessage(TF::DARK_RED . 'That user is opped!');
			return;
		}


		$entry = $banlist->getEntry($name);

		if($entry !== null){
			if(is_null($entry->getExpires())){
				$sender->sendMessage(TF::GOLD . $name . TF::DARK_RED . ' is already blacklisted!');
				return;
			}
			$banlist->remove($name);
		}

		$sname = $sender->getName();
		$reason = implode(' ', array_slice($args, 1));

		$webHook = new Webhook('https://discordapp.com/api/webhooks/752996288659849247/-aeMwvNXSIQ8bJxvISqU-TT3sI5HfHnu2LM3dB8Jh-RHHXz0pQxqVZLBK8uQKMHAaKU5');
		$msg = new Message();
		$embed = new Embed();
		$embed->setTitle('Player Blacklisted!');
		$embed->setDescription("**Player:** $name\n**Moderator:** $sname\n**Reason:** $reason!");
		$embed->setFooter(date('m/d/Y @ h:i:s a', time()));
		$msg->addEmbed($embed);
		$webHook->send($msg);

		$target?->kick(
			TF::BOLD . TF::DARK_PURPLE . str_pad('Velvet', strlen("You have been blacklisted by $sname!"), pad_type: STR_PAD_BOTH) . "\n" . TF::RESET .
			TF::RED . 'You have been blacklisted by ' . TF::GREEN . "$sname!\n"
		);

		$banlist->addBan($name, $reason, null, $sender->getName());
		$sender->getServer()->broadcastMessage(
			TF::RED . $name . TF::AQUA . ' was blacklisted by ' . TF::GREEN . "$sname\n" .
			TF::RED . 'Reason: ' . TF::AQUA . $reason
		);
	}
}