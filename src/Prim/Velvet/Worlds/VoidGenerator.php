<?php

namespace Prim\Velvet\Worlds;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class VoidGenerator extends Generator {

	public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
		$chunk = $world->getChunk($chunkX, $chunkZ);

		$grass = VanillaBlocks::GRASS()->getFullId();
		$air = VanillaBlocks::AIR()->getFullId();
		for($x = 0; $x < 16; ++$x) {
			for ($z = 0; $z < 16; ++$z) {
				for($y = 0; $y < 128; ++$y) {
					if($chunkX === 0 && $chunkZ === 0){
						$chunk->setFullBlock(0, 64, 0, $grass);
					} else {
						$chunk->setFullBlock($x, $y, $z, $air);
					}
				}
			}
		}
		$world->setChunk($chunkX, $chunkZ, clone $chunk);
	}

	public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {}
}