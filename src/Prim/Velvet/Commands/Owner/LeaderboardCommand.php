<?php

namespace Prim\Velvet\Commands\Owner;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Managers\EntityManager;
use Prim\Velvet\Utils\Translator;
use function count;
use function file_put_contents;
use function in_array;
use function json_encode;

class LeaderboardCommand extends Command {

	public function __construct(){
		parent::__construct(
			'leaderboard',
			TF::LIGHT_PURPLE . 'Spawn in a leaderboard!',
			TF::RED . "Usage: " . TF::GRAY . "/leaderboard <kills:deaths:kdr:topkillstreak>"
		);
		$this->setPermission('velvet.leaderboards.manage');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) return;

		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if($sender->getXuid() !== Translator::OWNER_XUID){
			$sender->sendMessage(TF::DARK_RED . 'This is an owner only command!');
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		if(!in_array($args[0], ['kills', 'deaths', 'kdr', 'topkillstreak'])){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$data = EntityManager::getInstance()->getLeaderboards();
		$pos = $sender->getPosition();
		$data[$args[0]]['level'] = $sender->getWorld()->getDisplayName();
		$data[$args[0]]['position'] = [$pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()];
		file_put_contents(Main::getMain()->getDataFolder() . 'leaderboards.json', json_encode($data, JSON_PRETTY_PRINT));

		$sender->sendMessage(TF::GRAY . 'The position for leaderboard type ' . TF::LIGHT_PURPLE . $args[0] . TF::GRAY . ' has been changed. It will be active next server restart.');
	}

}