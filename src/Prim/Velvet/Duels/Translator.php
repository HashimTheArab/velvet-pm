<?php

namespace Prim\Velvet\Duels;

interface Translator {

	const KITS = [
		self::NODEBUFF => 'nodebuff',
		self::GAPPLE => 'gapple',
		self::DIAMOND => 'diamond',
		self::GOD => 'god',
		self::SUMO => 'sumo',
		self::LINE => 'sumo',
		self::REDROVER => 'nodebuff',
		self::GFIGHT => 'gfight'
	];

	const NODEBUFF = 'NoDebuff';
	const GAPPLE = 'Gapple';
	const DIAMOND = 'Diamond';
	const GOD = 'God';
	const SUMO = 'Sumo';
	const LINE = 'Line';

	const FREE = 0;
	const BUSY = 1;

	// Events

	const GFIGHT = "GFight";
	const REDROVER = "RedRover";

}