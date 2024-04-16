<?php

namespace Prim\Velvet\Managers;

use pocketmine\entity\Skin;
use pocketmine\player\Player;
use Prim\Velvet\Main;
use function chr;
use function file_exists;
use function imagecolorat;
use function imagecreatefrompng;
use function imagedestroy;
use function imagesx;
use function imagesy;
use function scandir;
use function strlen;

class CosmeticManager {

	public array $capes = [];

	public function __construct(public Main $main){
		$list = scandir($main->getDataFolder() . 'capes');
		if($list !== false) foreach($list as $name){
			if($name === '.' || $name === '..') continue;
			$this->capes[$name] = 1;
		}
	}

	public function setCape(Player $player, string $cape) : void {
		$oldSkin = $player->getSkin();
		$newSkin = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $cape, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
		$player->setSkin($newSkin);
		$player->sendSkin();
	}

	public function createCape(Player $player, string $file) : bool {
		$path = $this->main->getDataFolder() . "capes/$file";
		if (!file_exists($path)) return false;
		$img = imagecreatefrompng($path);
		$rgba = '';
		for ($y = 0; $y < imagesy($img); $y++) {
			for ($x = 0; $x < imagesx($img); $x++) {
				$rgb = imagecolorat($img, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$rgba .= chr($r) . chr($g) . chr($b) . chr(255);
			}
		}
		imagedestroy($img);
		if (strlen($rgba) !== 8192) return false;
		$this->setCape($player, $rgba);
		return true;
	}

}