<?php

namespace Prim\Velvet\Entities\FloatingText;

use pocketmine\player\Player;
use pocketmine\world\Position;
use Prim\Velvet\Main;

class FloatingText {

	//Credit to BoomYourBang for this whole class

	/** @var TextObject[] */
	public array $texts = [];

	public Main $main;

	public function __construct(Main $main){
		$this->main = $main;
		$this->register();
	}

	public function spawnAll(Player $player){
		foreach($this->texts as $text){
			$text->spawnTo($player);
		}
	}

	public function hideAll(Player $player){
		foreach($this->texts as $text){
			$text->hide($player, true);
		}
	}

	public function showAll(Player $player){
		foreach($this->texts as $text){
			$text->show($player, true);
		}
	}

	public function update(){
		foreach($this->getTexts() as $text){
			if($text instanceof TextObject) $text->update();
		}
	}

	public function getTexts() : array {
		return $this->texts;
	}

	public function getText(string $name) : ?TextObject {
		return $this->texts[$name] ?? null;
	}

	public function registerText(string $name, Position $position){
		$this->texts[$name] = new TextObject($name, $position);
	}

	public function register(){
		foreach($this->main->entityManager->getLeaderboards() as $leaderboard => $data){

			$pos = $data['position'];
			if(!isset($pos[2])){
				$this->main->getLogger()->critical("Invalid position for leaderboard: $leaderboard");
				continue;
			}

			$position = new Position($pos[0], $pos[1], $pos[2], $this->main->getServer()->getWorldManager()->getWorldByName($data['level']));
			$this->registerText($leaderboard, $position);
		}
	}
}