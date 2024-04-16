<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function count;

class BuildCommand extends Command{

	public function __construct(){
		parent::__construct(
			'build',
			TF::LIGHT_PURPLE . 'Access builder mode!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/build <player>'
		);
		$this->setPermission('velvet.build');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) > 0){
			if($sender instanceof Player && $sender->getXuid() !== Translator::OWNER_XUID){
				$sender->sendMessage(TF::DARK_RED . 'You do not have permission to set other players in builder mode!');
				return;
			}

			$p = Server::getInstance()->getPlayerByPrefix($args[0]);
			if(!$p instanceof VelvetPlayer){
				$sender->sendMessage(TF::RED . "That player is not online!");
				return;
			}

			$sender->sendMessage(TF::LIGHT_PURPLE . $p->getName() . TF::GRAY . ' is '  . ($p->hasFlag(Flags::BUILDING) ? 'no longer in' : 'now in') . TF::LIGHT_PURPLE . ' builder ' . TF::GRAY . 'mode!');
			$p->sendMessage(TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . ' has set you ' . ($p->hasFlag(Flags::BUILDING) ? 'out' : 'in') . TF::LIGHT_PURPLE . ' builder ' . TF::GRAY . 'mode!');
			$p->setFlag(Flags::BUILDING);
			return;
		}

		if(!$sender instanceof VelvetPlayer){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		$sender->sendMessage(TF::GRAY . 'You are ' . ($sender->hasFlag(Flags::BUILDING) ? 'no longer in' : 'now in') . TF::LIGHT_PURPLE . ' builder ' . TF::GRAY . 'mode!');
		$sender->setFlag(Flags::BUILDING);
	}


}