<?php

namespace Prim\Velvet\Commands\World\SubCommands;

use pocketmine\command\CommandSender;
use pocketmine\Server;
use Prim\Velvet\Commands\SubCommand;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function basename;
use function count;
use function file_exists;
use function is_dir;
use function is_file;
use function is_null;
use function rmdir;
use function scandir;
use function unlink;

class WorldDeleteSubCommand extends SubCommand {

	public function __construct(){
		parent::__construct(TF::RED . 'Usage: ' . TF::GRAY . '/mw delete <name>');
	}

	public function executeSub(CommandSender $sender, array $args) : void {
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		if(!$sender->getServer()->getWorldManager()->isWorldGenerated($args[0]) || !file_exists(Server::getInstance()->getDataPath() . "worlds/$args[0]")){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::RED . 'That world does not exist!');
			return;
		}

		if(!Server::getInstance()->getWorldManager()->isWorldLoaded($args[0])) Server::getInstance()->getWorldManager()->loadWorld($args[0]);

		if (!Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName() == Server::getInstance()->getWorldManager()->getWorldByName($args[0])->getFolderName()){
			$sender->sendMessage(Translator::WORLDS_PREFIX . TF::RED . 'Could not remove default level!');
			return;
		}

		$world = Server::getInstance()->getWorldManager()->getWorldByName($args[0]);
		if(is_null($world)){
			$sender->sendMessage(Translator::WORLDS_PREFIX . 'That world does not exist or failed to load!');
			return;
		}
		if(count($world->getPlayers()) > 0) {
			$spawn = Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation();
			foreach ($world->getPlayers() as $player) $player->teleport($spawn);
		}

		$path = $world->getProvider()->getPath();
		Server::getInstance()->getWorldManager()->unloadWorld($world);
		$this->removeDir($path);

		$sender->sendMessage(Translator::WORLDS_PREFIX . TF::GREEN . 'Successfully deleted the world ' . TF::YELLOW . $args[0] . '!');
	}

	public function removeDir(string $dirPath) {
		if(basename($dirPath) == "." || basename($dirPath) == "..") return;
		foreach (scandir($dirPath) as $item) {
			if(is_dir($dirPath . DIRECTORY_SEPARATOR . $item)) {
				$this->removeDir($dirPath . DIRECTORY_SEPARATOR . $item);
			}
			if(is_file($dirPath . DIRECTORY_SEPARATOR . $item)) {
				unlink($dirPath . DIRECTORY_SEPARATOR . $item);
			}

		}
		rmdir($dirPath);
	}

}