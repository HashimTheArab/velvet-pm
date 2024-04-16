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
use Prim\Velvet\Utils\Utils;
use Prim\Velvet\VelvetPlayer;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function date;
use function is_null;
use function time;

class BanCommand extends Command{

    public function __construct(){
        parent::__construct(
            'ban',
            TF::LIGHT_PURPLE . 'Ban another player from the server!' . Translator::COMMAND_STAFF,
            TF::RED . 'Usage: ' . TF::GRAY . '/ban <player> <time> <reason>' . TF::AQUA . "\nS - SECONDS | M - MINUTES | H - HOURS | D - DAYS | W - WEEKS | MO - MONTHS\n" . TF::GRAY . 'Example: ' . TF::AQUA . '/ban xPrim69x 1d hacking'
        );
        $this->setPermission("velvet.bans.manage");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender->hasPermission($this->getPermission())){
            $sender->sendMessage(Translator::NO_PERMISSION);
            return;
        }

		if(count($args) < 3) {
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $args[0];
		$target = Server::getInstance()->getPlayerByPrefix($name);
		$banlist = $sender->getServer()->getNameBans();

		if($target instanceof VelvetPlayer){
			if ($target->hasFlag(Flags::STAFF) && $sender->getName() !== 'xPrim69x') {
				$sender->sendMessage(TF::DARK_RED . 'You cannot ban a staff member!');
				return;
			}
			$name = $target->getName();
		} else {
			if(in_array(PermissionManager::getInstance()->getPlayerRank(Server::getInstance()->getOfflinePlayer($name)), Translator::STAFF_RANKS) && $sender->getName() !== 'xPrim69x' && $sender instanceof Player){
				$sender->sendMessage(TF::DARK_RED . 'You cannot ban a staff member!');
				return;
			}
		}

		if(Server::getInstance()->isOp($name) && $sender->getName() !== 'xPrim69x' && $sender instanceof Player){
			$sender->sendMessage(TF::DARK_RED . 'That user is opped!');
			return;
		}

		if($banlist->isBanned($name)){
			$sender->sendMessage(TF::GOLD . $name . TF::DARK_RED . ' is already ' . (is_null($banlist->getEntry($name)->getExpires()) ? 'blacklisted' : 'banned') . ' !');
			return;
		}

		$time = Utils::stringToTime($args[1]);
		if(is_null($time)){
			$sender->sendMessage(TF::RED . 'Enter a valid time! ' . TF::GRAY . '(S - SECONDS | M - MINUTES | H - HOURS | D - DAYS | W - WEEKS | MO - MONTHS)');
			return;
		}

		$sname = $sender->getName();
		$reason = implode(' ', array_slice($args, 2));
		$length = Utils::timeToStringDifference($time);

		$webHook = new Webhook('https://discordapp.com/api/webhooks/752996288659849247/-aeMwvNXSIQ8bJxvISqU-TT3sI5HfHnu2LM3dB8Jh-RHHXz0pQxqVZLBK8uQKMHAaKU5');
		$msg = new Message();
		$embed = new Embed();
		$embed->setTitle('Player Banned!');
		$embed->setDescription("**Player:** $name\n**Moderator:** $sname\n**Reason:** $reason!\n**Length:** $length");
		$embed->setFooter(date('m/d/Y @ h:i:s a', time()));
		$msg->addEmbed($embed);
		$webHook->send($msg);

		$target?->kick(TF::RED . 'You were banned by ' . TF::AQUA . $sname . TF::EOL . TF::RED . 'Reason: ' . TF::AQUA . $reason . TF::EOL . TF::RED . 'Length: ' . TF::AQUA . $length);

		$banlist->addBan($name, $reason, $time, $sname);
		$sender->getServer()->broadcastMessage(TF::RED . $name . TF::AQUA . ' was banned by ' . TF::GREEN . $sname . TF::EOL . TF::RED . 'Reason: ' . TF::AQUA . $reason . TF::EOL . TF::RED . 'Length: ' . TF::AQUA . $length);
    }
}