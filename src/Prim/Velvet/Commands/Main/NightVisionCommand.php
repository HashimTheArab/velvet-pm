<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;

class NightVisionCommand extends Command{

	public function __construct(){
		parent::__construct(
			'nightvision',
			TF::LIGHT_PURPLE . 'Gives you night vision!',
		);
		$this->setAliases(['nv']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender instanceof Player) {
			$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}
		if(!$sender->getEffects()->has(VanillaEffects::NIGHT_VISION())){
			$sender->getEffects()->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), 999999999, 255, false));
			$sender->sendMessage(TF::GREEN . 'You now have night vision!');
		} else {
			$sender->getEffects()->remove(VanillaEffects::NIGHT_VISION());
			$sender->sendMessage(TF::RED . 'You no longer have night vision!');
		}
	}
}