<?php

namespace Prim\Velvet\Games;

interface GameArenas {

	public const DEFAULT_LEVEL = 'events';

	public const ARENAS = [
		Game::TYPE_SUMO => [
			'default' => [259, 73, 410],
			'spawn1' => [251.5, 71, 379.5],
			'spawn2' => [265.5, 71, 379.5]
		],
		Game::TYPE_NODEBUFF => [ // *todo*
			'default' => [0, 0, 0],
			'spawn1' => [0, 0, 0],
			'spawn2' => [0, 0, 0]
		],
		Game::TYPE_GFIGHT => [
			'default' => [300, 79, 143],
			'spawn1' => [272, 66, 183],
			'spawn2' => [243, 66, 183]
		],
		Game::TYPE_REDROVER => [ // *todo*
			'default' => [255, 75, 246],
			'spawn1' => [255, 75, 246],
			'spawn2' => [265, 75, 246]
		]
	];
}