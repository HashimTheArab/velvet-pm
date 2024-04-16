<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\player\Player;
use Prim\Velvet\Forms\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use function count;
use function is_null;

class DuelCommand extends Command {

	private Main $main;

	public function __construct(Main $main){
		parent::__construct(
			'duel',
			TF::LIGHT_PURPLE . 'Send a duel request to a player!',
			TF::RED . 'Usage: ' . TF::GRAY . '/duel <name:accept>'
		);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}
		if($sender->getWorld()->getFolderName() !== Translator::LOBBY_WORLD){
			$sender->sendMessage(TF::RED . 'You must be at the lobby to use this command!');
			return;
		}
		$session1 = $this->main->sessionManager->getSession($sender);
		if($session1->hasMatch()){
			$sender->sendMessage(TF::RED . 'You cannot use this command while in a duel!');
			return;
		}
		if($session1->hasParty()){
			$sender->sendMessage(TF::RED . 'You cannot use this command while in a party!');
			return;
		}
		if($args[0] === 'accept'){
			$owner = $session1->getLastInviteOwner();
			if($owner !== null){
				if($owner->hasMatch()){
					$sender->sendMessage(TF::RED . 'That player is in a duel!');
					return;
				}
				if($owner->hasParty()){
					$sender->sendMessage(TF::RED . 'That player is in a party!');
					return;
				}
				$player2 = $owner->getOwner();
				if($player2->getWorld()->getFolderName() !== Translator::LOBBY_WORLD){
					$sender->sendMessage(TF::RED . 'That player is not in the lobby!');
					return;
				}

				$type = $session1->getLastInviteType($owner);
				$a = $this->main->arenaManager->getArenaByMode($type);

				if(is_null($a)){
					$sender->sendMessage(TF::GRAY . "All " . TF::LIGHT_PURPLE . $type . TF::GRAY . " arenas are currently full! The duel request has been cancelled.");
					$player2->sendMessage(TF::LIGHT_PURPLE . $sender->getName() . TF::GRAY . " tried accepting your duel request but all the " . TF::LIGHT_PURPLE . $type . TF::GRAY . " arenas are currently full! The duel request has been cancelled.");
					$session1->clearAllInvites();
					return;
				}

				$this->main->matchManager->startMatch($sender, $player2, $type);
				$session1->clearAllInvites();
			} else {
				$sender->sendMessage(TF::RED . "You do not have any duel invitations.");
			}
		} else {
			$player = $this->main->getServer()->getPlayerByPrefix($args[0]);

			if(is_null($player) || !$player->spawned){
				$sender->sendMessage(TF::RED . 'That player is not online!');
				return;
			}
			if($player->getId() === $sender->getId()){
				$sender->sendMessage(TF::RED . "You cannot invite yourself!");
				return;
			}

			$session2 = $this->main->sessionManager->getSession($player);

			if($session2->hasMatch()){
				$sender->sendMessage(TF::RED . "That player is in a duel!");
				return;
			}
			if($session2->hasParty()){
				$sender->sendMessage(TF::RED . "That player is in a party!");
				return;
			}

			$form = new SimpleForm(function (Player $sender, int $result = null) use ($session1, $session2, $player){
				if(is_null($result)) return;
				if(is_null($player) || !$player->isOnline()){
					$sender->sendMessage(TF::RED . 'That player is no longer online!');
					return;
				}
				$type = Translator::DUEL_MODES[$result];
				if($type !== null){
					$session2->addInviteFrom($session1, $type);
					$sender->sendMessage(TF::AQUA . 'You have invited ' . TF::WHITE . $player->getName() . TF::AQUA . " to a $type duel!");
					$player->sendMessage(TF::WHITE . $sender->getName() . TF::AQUA . " has invited you to a $type duel! Do /duel accept to accept!");
				}
			});
			$form->setTitle("§l§5Duels!");
			$form->setContent("§bChoose a mode for your fight!");
			$form->addButton("§l§9Nodebuff", 0, "textures/items/potion_bottle_splash_heal");
			$form->addButton("§l§6Gapple", 0, "textures/items/apple_golden");
			$form->addButton("§l§bDiamond", 0, "textures/items/diamond_sword");
			$form->addButton("§l§4God", 0, "textures/items/nether_star");
			$form->addButton("§l§3Sumo", 0, "textures/ui/slow_falling_effect");
			$form->addButton("§l§2Line", 0, "textures/items/lead");
			$sender->sendForm($form);
		}
	}

}