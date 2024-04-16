<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function array_slice;
use function count;
use function implode;

class ForceTellCommand extends Command{

	public function __construct(){
		parent::__construct(
			'forcetell',
			TF::LIGHT_PURPLE . 'Send a forced message (bypasses muted chat) to another player!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/ft <name> <message>',
			['ft']
		);
		$this->setPermission('velvet.forcetell');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 2){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$target = Server::getInstance()->getPlayerByPrefix($args[0]);

		if($target instanceof Player){
			$m = implode(' ', array_slice($args, 1));
			$pk = new TextPacket();
			$pk->type = TextPacket::TYPE_TRANSLATION;
			$pk->message = TF::DARK_RED . '[Forced] ' . TF::DARK_PURPLE . '[' . TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' -> ' . TF::LIGHT_PURPLE . 'You' . TF::DARK_PURPLE . '] ' . TF::RED . $m;
			$target->getNetworkSession()->sendDataPacket($pk);
			$sender->sendMessage(TF::DARK_RED . '[Forced] ' . TF::DARK_PURPLE . '[' . TF::LIGHT_PURPLE . 'You' . TF::GRAY . ' -> ' . TF::LIGHT_PURPLE . $target->getName() . TF::DARK_PURPLE . '] ' . TF::RED . $m);
		} else {
			$sender->sendMessage(TF::RED . "$args[0] is not online!");
		}
	}

}