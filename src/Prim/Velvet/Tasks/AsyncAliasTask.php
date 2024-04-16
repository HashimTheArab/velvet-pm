<?php

namespace Prim\Velvet\Tasks;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use function array_map;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_null;
use function is_string;
use function json_decode;
use function strtolower;

class AsyncAliasTask extends AsyncTask {

	public string $sender;
	public string $target;
	public int|null|string $deviceID;
	public string $path;

	public function __construct(string $sender, string $target, string $path){
		$this->sender = $sender;
		$this->target = $target;
		$this->path = $path;
	}

	public function onRun() : void {
		if(!file_exists($this->path)) file_put_contents($this->path, '{}');
		$array = json_decode(file_get_contents($this->path), true);

		$deviceID = null;
		foreach($array as $key => $section){
			if(in_array(strtolower($this->target), array_map('strtolower', $section))){
				$deviceID = $key;
			}
		}
		if(is_null($deviceID)){
			$this->setResult(TF::RED . "$this->target has never joined the server before.");
			return;
		}
		$this->setResult($array[$deviceID]);
		$this->deviceID = $deviceID;
	}

	public function onCompletion() : void {
		$result = $this->getResult();
		$sender = strtolower($this->sender) === 'console' ? new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()) : Server::getInstance()->getPlayerExact($this->sender);
		if(is_string($result)){
			$sender?->sendMessage($result);
		} else {
			$banlist = Server::getInstance()->getNameBans();
			foreach($result as $key => $name){
				if($banlist->isBanned($name)){
					$entry = $banlist->getEntry($name);
					$result[$key] = $name . TF::DARK_RED . TF::BOLD . (is_null($entry->getExpires()) ? ' BLACKLISTED' : ' BANNED') . TF::RESET . TF::RED;
				}
			}
			$sender?->sendMessage(TF::BLUE . 'Alias information for ' . TF::RED . TF::BLUE . 'Device ID (' . TF::RED . $this->deviceID . TF::BLUE . '): ' . TF::RED . implode(', ', $result));
		}
	}

}