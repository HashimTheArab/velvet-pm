<?php

namespace Prim\Velvet\Utils;

use function file_exists;
use function file_put_contents;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function in_array;

class AliasHelper {

	const DEVICE_ID = 'deviceid';

	public static function getFile(string $dataPath, string $type) : bool|string {
		$path = $dataPath . "alias/$type.json";
		if(!file_exists($path)) file_put_contents($path, '{}');
		return file_get_contents($path);
	}

	public static function did2Gamertags(string $dataPath, string $deviceID, ?string $name = null, bool $save = false): array{ //if returns empty, no gamertags for that cid
		$array = json_decode(self::getFile($dataPath,self::DEVICE_ID), true);
		if($save){
			if(isset($array[$deviceID]) && !in_array($name, $array[$deviceID])){
				$array[$deviceID][] = $name;
			} else {
				$array[$deviceID] = [$name];
			}
			file_put_contents($dataPath . 'alias/' . self::DEVICE_ID . '.json', json_encode($array, JSON_PRETTY_PRINT));
		}
		return $array[$deviceID] ?? [];
	}

}