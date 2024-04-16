<?php

namespace Prim\Velvet\Commands\Owner;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Translator;
use function count;
use function is_null;
use function is_numeric;

class SetKillstreakCommand extends Command {

	public function __construct(){
		parent::__construct(
			'setkillstreak',
			TF::LIGHT_PURPLE . 'Set a players killstreak! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/setkillstreak <player> <killstreak>'
		);
		$this->setPermission('velvet.stats.manage');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if($sender instanceof Player && $sender->getXuid() !== Translator::OWNER_XUID){
			$sender->sendMessage(TF::DARK_RED . 'This is an owner only command!');
			return;
		}

		if(count($args) < 2) {
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$target = $sender->getServer()->getPlayerExact($args[0]);
		if(is_null($target) || !$target->spawned){
			$sender->sendMessage(TF::RED . 'That player is not online! (Exact Name Needed)');
			return;
		}

		if(!is_numeric($args[1])){
			$sender->sendMessage(TF::RED . 'The killstreak must be a number!');
			return;
		}

		$session = SessionManager::getInstance()->getSession($target);
		$sender->sendMessage(TF::YELLOW . $target->getName() . 's ' . TF::GREEN . 'killstreak has been set from ' . TF::YELLOW . $session->killstreak . TF::GREEN . ' to ' . TF::YELLOW . $args[1] . '!');
		$session->killstreak = $args[1];
	}

}