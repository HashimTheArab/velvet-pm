<?php

declare(strict_types=1);

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function array_slice;
use function count;
use function implode;
use function strpos;

class SudoCommand extends Command {

	public function __construct(){
		parent::__construct(
			'sudo',
			TF::LIGHT_PURPLE . 'Send a message or command as another player!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/sudo <player> </command|chat>'
		);
		$this->setPermission('velvet.sudo');
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

		$target = $sender->getServer()->getPlayerByPrefix($args[0]);

		if($target->getName() === $sender->getName()){
			$sender->sendMessage(TF::DARK_RED . 'You can\'t sudo yourself!');
			return;
		}

		if(!$target instanceof Player){
			$sender->sendMessage(TF::DARK_RED . 'That player is not online!');
			return;
		}

		$msg = implode(' ', array_slice($args, 1));
		$target->chat($msg);
		$sender->sendMessage(TF::GREEN . (!strpos($msg, '/') ? 'Message sent as ' : 'Command executed as ') . TF::YELLOW . $target->getName());
	}

}