<?php

namespace Prim\Velvet\Tasks;

use Error;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;
use Prim\Velvet\Utils\Translator;
use function is_null;

class BuildResetTask extends Task {

	public int $minX = 261;
	public int $maxX = 381;

	public int $minY = 65;
	public int $maxY = 106;

	public int $minZ = 146;
	public int $maxZ = 256;

	public ?World $world;
	public SubChunkExplorer $explorer;

	public function __construct(){
		$this->world = Server::getInstance()->getWorldManager()->getWorldByName(Translator::BUILDUHC_WORLD);
		$this->explorer = new SubChunkExplorer($this->world);
		$this->loadChunks($this->world);
	}

	public function onRun() : void {
		for($x = $this->minX; $x <= $this->maxX; ++$x) {
			for($z = $this->minZ; $z <= $this->maxZ; ++$z) {
				for($y = $this->minY; $y <= $this->maxY; ++$y) {
					$this->setBlockAt($x, $y, $z);
				}
			}
		}
		$this->reloadChunks($this->world);
		$this->explorer->invalidate();
	}

	public function setBlockAt(int $x, int $y, int $z) : void {
		$this->explorer->moveTo($x, $y, $z);

		if(is_null($this->explorer->currentSubChunk)){
			try {
				$this->explorer->currentSubChunk = $this->explorer->currentChunk->getSubChunk($y >> 4);
			} catch (Error) {
				return;
			}
		}

		$this->explorer->currentSubChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, 0);
	}

	public function loadChunks(World $world) : void {
		$minX = $this->minX >> 4;
		$maxX = $this->maxX >> 4;
		$minZ = $this->minZ >> 4;
		$maxZ = $this->maxZ >> 4;

		for($x = $minX; $x <= $maxX; $x++) {
			for($z = $minZ; $z <= $maxZ; $z++) {
				if(is_null($world->getChunk($x, $z))) $world->loadChunk($x, $z);
			}
		}
	}

	public function reloadChunks(World $world): void {
		$minX = $this->minX >> 4;
		$maxX = $this->maxX >> 4;
		$minZ = $this->minZ >> 4;
		$maxZ = $this->maxZ >> 4;

		for($x = $minX; $x <= $maxX; ++$x) {
			for($z = $minZ; $z <= $maxZ; ++$z) {
				$chunk = $world->getChunk($x, $z);
				if(is_null($chunk)) continue;
				$world->setChunk($x, $z, $chunk);
			}
		}
	}

}