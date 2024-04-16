<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Utils\Translator;
use function is_null;

class PingCommand extends Command{

    public function __construct(){
        parent::__construct(
            'ping',
            TF::LIGHT_PURPLE . 'Check a players ping!'
        );
        $this->setAliases(['ms']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
        	$sender->sendMessage(Translator::INGAME_ONLY);
			return;
		}

		$ping = $sender->getNetworkSession()->getPing();
		$name = "Your";

        if(isset($args[0])){
            $name = $args[0];
            $target = Server::getInstance()->getPlayerByPrefix($name);
            if(is_null($target)){
                $sender->sendMessage(TF::RED . "$name is not online!");
                return;
            }
            $ping = $target->getNetworkSession()->getPing();
			$name = $target->getName() . '\'s';
        }

		$sender->sendMessage(TF::LIGHT_PURPLE . "$name ping is " . TF::GREEN . "$ping ms");
    }
}