<?php

namespace Prim\Velvet\Commands\Staff;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Main;
use Prim\Velvet\Tasks\AsyncAliasTask;
use Prim\Velvet\Utils\AliasHelper;
use Prim\Velvet\Utils\Translator;
use function count;

class AliasCommand extends Command {

	public function __construct(){
		parent::__construct(
			'alias',
			TF::LIGHT_PURPLE . 'Check for alts by device!' . Translator::COMMAND_STAFF,
			TF::RED . 'Usage: ' . TF::GRAY . '/alias <player>'
		);
		$this->setPermission('velvet.alias');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$sender->hasPermission($this->getPermission())){
			$sender->sendMessage(Translator::NO_PERMISSION);
			return;
		}

		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $args[0];
		$target = Server::getInstance()->getPlayerByPrefix($name);

		if($target !== null){
			$name = $target->getName();
		}

		Server::getInstance()->getAsyncPool()->submitTask(new AsyncAliasTask($sender->getName(), $name, Main::getMain()->getDataFolder() . 'alias/' . AliasHelper::DEVICE_ID . '.json'));
	}

}