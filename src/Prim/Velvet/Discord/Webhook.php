<?php

declare(strict_types = 1);

namespace Prim\Velvet\Discord;

use pocketmine\Server;
use function filter_var;

class Webhook {

	protected string $url;

	public function __construct(string $url){
		$this->url = $url;
	}

	public function getURL() : string {
		return $this->url;
	}

	public function isValid() : bool {
		return filter_var($this->url, FILTER_VALIDATE_URL) !== false;
	}

	public function send(Message $message) : void {
		Server::getInstance()->getAsyncPool()->submitTask(new DiscordWebhookSendTask($this, $message));
	}
}