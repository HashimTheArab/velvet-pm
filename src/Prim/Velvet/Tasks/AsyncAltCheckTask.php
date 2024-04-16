<?php

namespace Prim\Velvet\Tasks;

use pocketmine\permission\BanList;
use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\AliasHelper;

class AsyncAltCheckTask extends AsyncTask {

	public bool $ban = false;

	public function __construct(
		public string $deviceID,
		public string $name,
		public string $dataPath,
		public BanList $bans,
		public $callback
	){}


	public function onRun(): void{
		$accs = AliasHelper::did2Gamertags($this->dataPath, $this->deviceID, $this->name, true);

		foreach($accs as $acc) {
			if($this->bans->isBanned($acc) && $acc !== $this->name){
				$this->ban = true;
				return;
			}
		}
	}

	public function onCompletion(): void{
		if($this->ban){
			($this->callback)();
		}
	}

	public static function create(Player $player) : self {
		return new self($player->getPlayerInfo()->getExtraData()['DeviceId'], $player->getName(), Main::getMain()->getDataFolder(), Server::getInstance()->getNameBans(), fn() => $player->transfer('vasar.land'));
	}

}