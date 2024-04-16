<?php

namespace Prim\Velvet\Anticheat\Checks\Autoclicker;

use pocketmine\network\mcpe\protocol\ServerboundPacket;
use Prim\Velvet\Anticheat\Checks\Check;
use Prim\Velvet\Sessions\Session;

class AutoclickerA extends Check {

	public function run(ServerboundPacket $packet, Session $session) : void {
		$player = $session->getOwner();
		if($player->getClicks() > 19){
			$this->flag($session, ['cps' => $player->getClicks()]);
		}
	}
}