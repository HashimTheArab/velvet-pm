<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Permissions\PermissionManager;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Scoreboard;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\Utils\Utils;
use Prim\Velvet\VelvetPlayer;
use function count;
use function date;
use function in_array;
use function strlen;
use function strtolower;
use function time;

class NickCommand extends Command {

	public function __construct(){
		parent::__construct(
			'nick',
			TF::LIGHT_PURPLE . 'Disguise yourself as another name!',
			TF::RED . 'Usage: ' . TF::GRAY . '/nick <name>'
		);
		$this->setPermission('velvet.nick');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if(!$sender instanceof VelvetPlayer){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(!$sender->hasFlag(Flags::NICKED) && count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		if($sender->hasFlag(Flags::NICKED) && count($args) < 1){
			$sender->sendMessage(TF::YELLOW . 'Your nick has been ' . TF::LIGHT_PURPLE . 'reset!');
			$sender->setFlag(Flags::NICKED);
			$sender->setDisplayName($sender->getName());
			$sender->setNameTag(PermissionManager::getInstance()->getRank(SessionManager::getInstance()->getSession($sender)->rank)->getNameTag($sender));
			Utils::whisperToStaff(TF::GRAY . '[' . TF::LIGHT_PURPLE . $sender->getName() . ':' . TF::GRAY . ' has reset their nick!]');
		} else {
			if(!$sender->getServer()->isOp($sender->getName())){
				if(strlen($args[0]) > 18){
					$sender->sendMessage(TF::GRAY . 'Nicknames must stay under ' . TF::LIGHT_PURPLE . '18' . TF::GRAY . ' characters!');
					return;
				}
				$names = [];
				foreach($sender->getServer()->getOnlinePlayers() as $p) $names[strtolower($p->getDisplayName())] = 1;
				if(isset($names[strtolower($args[0])]) || (in_array(strtolower($args[0]), Translator::STAFF_NAMES) && $sender->getXuid() !== Translator::OWNER_XUID)){
					$sender->sendMessage(TF::RED . 'You cannot nick as that player!');
					return;
				}
			}
			$sender->setDisplayName($args[0]);
			$sender->sendMessage(TF::YELLOW . 'Your nick has been set to ' . TF::LIGHT_PURPLE . $args[0] . '!');
			$sender->setFlag(Flags::NICKED);
			$sender->setNameTag(Utils::getDefaultNameTag($sender));
			Utils::whisperToStaff(TF::GRAY . '[' . TF::LIGHT_PURPLE . $sender->getName() . ':' . TF::GRAY . ' has nicked as ' . TF::LIGHT_PURPLE . $args[0] . TF::GRAY . ']');
		}

		Scoreboard::getInstance()->remove($sender);
		$sender->newScoreboard();

		$webHook = new Webhook(Translator::COMMANDS_WEBHOOK);
		$msg = new Message();
		$embed = new Embed();
		$embed->setTitle('Command Ran! (/nick)');
		$embed->setDescription("Player: {$sender->getName()}\nNick: " . ($args[0] ?? '**Disabled**'));
		$embed->setFooter(date('m/d/Y @ h:i:s a', time()));
		$msg->addEmbed($embed);
		$webHook->send($msg);
	}
}