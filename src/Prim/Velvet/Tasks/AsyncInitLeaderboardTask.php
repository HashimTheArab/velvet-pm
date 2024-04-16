<?php

namespace Prim\Velvet\Tasks;

use pocketmine\scheduler\AsyncTask;
use Prim\Velvet\Entities\FloatingText\FloatingText;
use Prim\Velvet\Main;
use function scandir;
use function strlen;
use function json_decode;
use function file_get_contents;
use function array_slice;
use function arsort;
use function serialize;
use function unserialize;
use function number_format;

class AsyncInitLeaderboardTask extends AsyncTask {

	public string $data;
	public string $dir;

	public function __construct(string $directory){
		$this->dir = $directory;
		$this->data = serialize(['kills' => [], 'deaths' => [], 'topkillstreak' => [], 'kdr' => []]);
	}

	public function onRun() : void {
		$list = unserialize($this->data);
		foreach((array) scandir($this->dir) as $xuid){
			if(strlen($xuid) < 5) continue;
			$f = json_decode(file_get_contents($this->dir . $xuid), true);
			foreach($list as $type => $_){
				if($type === 'kdr'){
					$list[$type][$f['name']] = $this->formatKDR($f['kills'], $f['deaths']);
					continue;
				}
				$list[$type][$f['name']] = $f[$type];
			}
		}
		foreach($list as $type => $data){
			arsort($data);
			$list[$type] = array_slice($data, 0, 10, true);
		}
		$this->setResult($list);
	}

	public function onCompletion() : void {
		$main = Main::getMain();
		$main->entityManager->leaderboardInfo = $this->getResult();

		if(!empty($main->entityManager->getLeaderboards())){
			$main->entityManager->floatingText = new FloatingText($main);
			$main->entityManager->floatingText->update();
		}
	}

	public function formatKDR(int $kills, int $deaths) : float|string {
		if($deaths !== 0){
			$ratio = $kills / $deaths;
			if($ratio !== 0){
				return number_format($ratio, 1);
			}
		}
		return 0.0;
	}

}