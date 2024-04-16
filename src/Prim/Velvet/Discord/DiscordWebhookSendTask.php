<?php

declare(strict_types = 1);

namespace Prim\Velvet\Discord;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function json_encode;

class DiscordWebhookSendTask extends AsyncTask {

	protected Webhook $webhook;
	protected Message $message;

	public function __construct(Webhook $webhook, Message $message){
		$this->webhook = $webhook;
		$this->message = $message;
	}

	public function onRun() : void {
		$ch = curl_init($this->webhook->getURL());
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->message));
		curl_setopt($ch, CURLOPT_POST,true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
		$this->setResult(curl_exec($ch));
		curl_close($ch);
	}

	public function onCompletion() : void {
		$response = $this->getResult();
		if($response !== ""){
			Server::getInstance()->getLogger()->error("[DiscordWebhookAPI] Got error: " . $response);
		}
	}
}