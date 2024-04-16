<?php

namespace Prim\Velvet\Commands\World\SubCommands;

use pocketmine\command\CommandSender;
use Prim\Velvet\Commands\SubCommand;
use pocketmine\utils\TextFormat as TF;
use function count;
use function implode;
use function scandir;

class WorldListSubcommand extends SubCommand {

	public function executeSub(CommandSender $sender, array $args) : void {
		$worlds = [];

		$files = scandir($sender->getServer()->getDataPath() . 'worlds');
		$manager = $sender->getServer()->getWorldManager();
		foreach ($files as $name) {
			if ($manager->isWorldGenerated($name)) {
				if($manager->isWorldLoaded($name)) {
					$worlds[] = "§7$name » §aLoaded§7, " . count($manager->getWorldByName($name)->getPlayers()) . ' Players';
				} else {
					$worlds[] = "§7$name » §cUnloaded";
				}
			}
		}

		$sender->sendMessage(TF::GREEN . 'Worlds: (' . count($worlds) . "):\n" . implode("\n", $worlds));
	}

}