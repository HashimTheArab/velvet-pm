<?php

namespace Prim\Velvet\Anticheat;

use function implode;

class FlagData {

	public float $flags = 0;
	public array $data = [];

	public function getDataString() : string {
		$string = '';
		foreach($this->data as $key => $values){
			$string .= "$key: [" . implode(', ', $values) . "]\n";
		}
		return $string;
	}

}