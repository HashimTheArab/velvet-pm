<?php

namespace Prim\Velvet\Forms;

use pocketmine\player\Player;
use pocketmine\form\Form as IForm;

abstract class Form implements IForm {

	protected array $data = [];

	/** @var callable */
	private $callable;

	public function __construct(?callable $callable) {
		$this->callable = $callable;
	}

	public function getCallable() : ?callable {
		return $this->callable;
	}

	public function setCallable(?callable $callable) {
		$this->callable = $callable;
	}

	public function handleResponse(Player $player, $data) : void {
		$this->processData($data);
		$callable = $this->getCallable();
		if($callable !== null) {
			$callable($player, $data);
		}
	}

	public function processData(&$data) : void {
	}

	public function jsonSerialize(){
		return $this->data;
	}

}
