<?php

namespace Prim\Velvet\Tasks;

use pocketmine\scheduler\AsyncTask;
use Prim\Velvet\Sessions\SessionManager;
use function file_get_contents;
use function json_decode;

class AsyncDataLoadTask extends AsyncTask {

	public string $player;
	public string $path;

	public function __construct(string $player, string $path){
		$this->player = $player;
		$this->path = $path;
	}

	public function onRun() : void {
		$data = json_decode(file_get_contents($this->path), true);
		$this->setResult($data);
	}

	public function onCompletion() : void {
		SessionManager::getInstance()->getSessionByName($this->player)?->onLogin($this->getResult());
	}

}