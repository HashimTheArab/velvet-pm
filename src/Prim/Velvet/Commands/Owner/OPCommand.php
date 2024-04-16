<?php

namespace Prim\Velvet\Commands\Owner;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function count;

class OPCommand extends Command {

	public function __construct(){
		parent::__construct(
			'op',
			TF::LIGHT_PURPLE . 'Set a player as OP! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/op <player>'
		);
		$this->setPermission('velvet.command.op');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(TF::DARK_RED . Translator::NO_PERMISSION);
			return;
		}

		if($sender instanceof Player){
			$sender->sendMessage(TF::DARK_RED . "This command can only be executed from console!");
			return;
		}

		if(count($args) < 1) {
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $args[0];
		if(!Player::isValidUserName($name)){
			throw new InvalidCommandSyntaxException();
		}

		$player = $sender->getServer()->getOfflinePlayer($name);
		Command::broadcastCommandMessage($sender, new Translatable("commands.op.success", [$player->getName()]));
		if($player instanceof Player) $player->sendMessage(TF::GRAY . 'You are now op!');
		$sender->getServer()->addOp($player->getName());
	}

}