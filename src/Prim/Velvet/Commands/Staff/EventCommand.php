<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use function array_keys;
use function count;
use function implode;
use function is_null;

class EventCommand extends Command {

	public Main $main;

	public function __construct(Main $main){
		parent::__construct(
			'event',
			TF::LIGHT_PURPLE . 'Manage Events!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/event [list]'
		);
		$this->main = $main;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player){
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		if(count($args) < 1){
			if(!$sender->hasPermission('velvet.event')){
				$sender->sendMessage($this->usageMessage);
				return;
			}
			if(is_null($this->main->game)){
				$this->main->forms->gameCreateForm($sender);
				return;
			}
			$this->main->forms->manageGame($sender);
		} else {
			switch ($args[0]) {
				case 'list':
					$game = $this->main->game;
					if(is_null($game)){
						$sender->sendMessage(TF::RED . 'There is no event going on!');
					} else {
						$sender->sendMessage(TF::GREEN . 'Participants: (' . count($game->participants) . ') ' . implode(', ', array_keys($game->participants)));
						$sender->sendMessage(TF::GREEN . 'Fighters: (' . count($game->fighting) . ') ' . implode(', ', array_keys($game->fighting)));
						$sender->sendMessage(TF::GREEN . 'Spectators: (' . count($game->spectators) . ') ' . implode(', ', array_keys($game->spectators)));
					}
					break;
				case 'test':
					$this->main->game->addPlayer($sender);
					break;
				default:
					$sender->sendMessage($this->usageMessage);
			}
		}
	}

}