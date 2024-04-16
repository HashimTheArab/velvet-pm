<?php

namespace Prim\Velvet\Tasks;

use pocketmine\scheduler\Task;
use Prim\Velvet\Duels\NormalMatch;
use Prim\Velvet\Duels\MatchManager;
use function in_array;

Class MatchTask extends Task{

	private MatchManager $manager;

	public function __construct(MatchManager $manager){
		$this->manager = $manager;
	}

	public function onRun(): void{
		foreach($this->manager->getMatches() as $match){
			if(!$match instanceof NormalMatch) return;
			if($match->type === 'unranked' || $match->type === 'bot'){
				$match->doTick();
			}
		}
	}

}