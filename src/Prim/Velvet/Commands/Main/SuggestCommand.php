<?php

namespace Prim\Velvet\Commands\Main;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use Prim\Velvet\Discord\Embed;
use Prim\Velvet\Discord\Message;
use Prim\Velvet\Discord\Webhook;
use Prim\Velvet\Main;
use Prim\Velvet\Utils\Translator;
use Prim\Velvet\VelvetPlayer;
use function count;
use function implode;
use function time;

class SuggestCommand extends Command {

	public Main $main;

	public function __construct(Main $main){
		parent::__construct(
			'suggest',
			TF::LIGHT_PURPLE . 'Make a suggestion!',
			TF::RED . 'Usage: ' . TF::GRAY . '/suggest <suggestion>'
		);
		$this->main = $main;
		$this->setAliases(['suggestion']);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) < 1){
			$sender->sendMessage($this->usageMessage);
			return;
		}

		$name = $sender->getName();
		if($sender instanceof VelvetPlayer){
			if (isset($this->main->suggestCooldown[$name]) && time() - $this->main->suggestCooldown[$name] < 600) {
				$cd = 600 - (time() - $this->main->suggestCooldown[$name]);
				$sender->sendMessage(TF::RED . 'You are on cooldown for this command! You may use it again in ' . TF::AQUA . $cd . TF::RED . ' ' . ($cd === 1 ? 'second' : 'seconds') . '! ' . TF::AQUA . 'Reminder, abuse of this command can lead to a ban!');
				return;
			}
			$this->main->suggestCooldown[$name] = time();
		}

		$webhook = new Webhook('https://discord.com/api/webhooks/837548393072033812/8QiUeGrAsw2MHKERpAj7LGSV-kYKCe1ZzK8iKDMjoWyu43yhuSFXC9lWpW2d5Ri3m5BZ');
		$msg = new Message();
		$embed = new Embed();
		$embed->setAuthor("$name - InGame|Practice", null, 'https://images-ext-2.discordapp.net/external/-9dacSULDXERpXmRF9k2OsT6IooRtOK8qjckfYig3ZY/https/cdn.discordapp.com/icons/674077546089807888/7528dd52939c98d402c351fd1138c28b.webp');
		$embed->setDescription('**' . implode(' ', $args) . '**');
		$embed->setFooter('This suggestion was sent from in-game!');
		$msg->addEmbed($embed);
		$webhook->send($msg);

		$sender->sendMessage(TF::GREEN . 'Thank you for your input! Your suggestion has been sent to the Velvet discord server. View it at ' . TF::YELLOW . Translator::DISCORD_LINK . '!');
	}
}
