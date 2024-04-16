<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Utils\Translator;
use function date;
use function time;
use function count;

class UnbanCommand extends Command{

	public function __construct(){
		parent::__construct(
			'unban',
			TF::LIGHT_PURPLE . 'Unban a player from the server!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/unban <player>'
		);
		$this->setPermission('velvet.bans.manage');
		$this->setAliases(['pardon']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $args[0];
		$banlist = $sender->getServer()->getNameBans();

		if(!$banlist->isBanned($name)){
			$sender->sendMessage(TF::RED . "That player is not banned!");
			return;
		}

		$webHook = new Webhook('https://discord.com/api/webhooks/812151702706585623/62QXehZ58BXZ4_XtuYozXGrxcY1lMRwe5OccoiXtdIeJHG8lm4ctCv47zRM1iqjucIhW');
		$msg = new Message();
		$embed = new Embed();
		$embed->setTitle('Player Unbanned!');
		$embed->setDescription("**Player:** $name\n**Moderator:** " . $sender->getName() . "\n**Server:** Practice");
		$embed->setFooter(date('m/d/Y @ h:i:s a', time()));
		$msg->addEmbed($embed);
		$webHook->send($msg);

		$banlist->remove($name);
		$sender->sendMessage(TF::GREEN . 'You have successfully unbanned ' . TF::WHITE . "$name!");
	}
}