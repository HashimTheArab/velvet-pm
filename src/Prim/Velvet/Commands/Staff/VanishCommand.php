<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Sessions\SessionManager;
use Prim\Velvet\Utils\Flags;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\Utils\Utils;
use Prim\Velvet\VelvetPlayer;
use function count;

class VanishCommand extends Command{

	public function __construct(){
		parent::__construct(
			'vanish',
			TF::LIGHT_PURPLE . 'Hide yourself from other players! ' . TF::GREEN . '(Staff!)',
			TF::RED . 'Usage: ' . TF::GRAY . '/vanish [player]'
		);
		$this->setPermission('velvet.vanish');
		$this->setAliases(['v']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) > 0){
			if(!$sender->hasPermission('vanish.use.other')){
				$sender->sendMessage(TF::RED . "You do not have permission to vanish other players");
				return;
			}
			$player = Server::getInstance()->getPlayerByPrefix($args[0]);
			if ($player instanceof VelvetPlayer) {
				$this->vanishOther($sender, $player, !$player->hasFlag(Flags::VANISHED));
			} else {
				$sender->sendMessage(TF::RED . 'That player is not online!');
			}
		} else {
			if(!$sender instanceof VelvetPlayer){
				$sender->sendMessage(Translator::INGAME_ONLY);
				return;
			}
			$this->vanish($sender, !$sender->hasFlag(Flags::VANISHED));
		}
	}

	public function vanish(VelvetPlayer $player, bool $vanish = true){
		if($vanish){
			$player->setFlag(Flags::VANISHED);
			$player->sendMessage(TF::GREEN . 'You are now vanished!');
			$player->setNameTag(TF::GOLD . '[V] ' . TF::RESET . $player->getNameTag());
			if($player->getGamemode()->equals(GameMode::SURVIVAL())) {
				$player->setFlying(true);
				$player->setAllowFlight(true);
			}
			/** @var VelvetPlayer $p */
			foreach($player->getServer()->getOnlinePlayers() as $p){
				if($p->hasFlag(Flags::STAFF)) $p->sendMessage(TF::GRAY . TF::ITALIC . "[{$player->getName()}: Vanished]");
			}
		} else {
			if($player->hasFlag(Flags::NICKED)){
				$player->setNameTag(Utils::getDefaultNameTag($player));
			} else {
				$player->setNameTag(SessionManager::getInstance()->getSession($player)->getNameTag());
			}
			$player->setFlag(Flags::VANISHED);
			foreach ($player->getWorld()->getPlayers() as $p) {
				$p->showPlayer($player);
			}
			/** @var VelvetPlayer $p */
			foreach($player->getServer()->getOnlinePlayers() as $p){
				if($p->hasFlag(Flags::STAFF)) $p->sendMessage(TF::GRAY . TF::ITALIC . "[{$player->getName()}: Unvanished]");
			}
			if($player->getGamemode()->equals(GameMode::SURVIVAL())) {
				$player->setFlying(false);
				$player->setAllowFlight(false);
			}
			$player->sendMessage(TF::RED . 'You are no longer vanished!');
		}
	}

	public function vanishOther(CommandSender $sender, VelvetPlayer $player, bool $vanish = true){
		$name = $player->getName();
		$sname = $sender->getName();
		if($vanish){
			$player->setFlag(Flags::VANISHED);
			$sender->sendMessage(TF::GREEN . "Vanished $name");
			$player->sendMessage(TF::GREEN . "You have been vanished by $sname!");
			$player->setNameTag(TF::GOLD . '[V] ' . TF::RESET . $player->getNameTag());
			if($player->getGamemode()->equals(GameMode::SURVIVAL())) {
				$player->setFlying(true);
				$player->setAllowFlight(true);
			}
			/** @var VelvetPlayer $p */
			foreach($player->getServer()->getOnlinePlayers() as $p){
				if($p->hasFlag(Flags::STAFF)) $p->sendMessage(TF::GRAY . TF::ITALIC . "[$name set vanished by $sname]");
			}
		} else {
			if($player->hasFlag(Flags::NICKED)){
				$player->setNameTag(Utils::getDefaultNameTag($player));
			} else {
				$player->setNameTag(SessionManager::getInstance()->getSession($player)->getNameTag());
			}
			$player->setFlag(Flags::VANISHED);
			foreach ($player->getWorld()->getPlayers() as $p) {
				$p->showPlayer($player);
			}
			/** @var VelvetPlayer $p */
			foreach($player->getServer()->getOnlinePlayers() as $p){
				if($p->hasFlag(Flags::STAFF)) $p->sendMessage(TF::GRAY . TF::ITALIC . "[$name set unvanished by $sname]");
			}
			if($player->getGamemode()->equals(GameMode::SURVIVAL())) {
				$player->setFlying(false);
				$player->setAllowFlight(false);
			}
			$sender->sendMessage(TF::RED . "Unvanished $name!");
			$player->sendMessage(TF::RED . "You have been unvanished by $sname!");
		}
	}

}
