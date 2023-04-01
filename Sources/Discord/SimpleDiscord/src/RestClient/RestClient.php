<?php

namespace SimpleDiscord\RestClient;

class RestClient {
	private $headers;
	
	public $discord;

	public $gateway;
	public $user;
	public $channel;
	public $guild;

	const BASE_URI = "https://discord.com/api/v10/";

	public function __construct(array $headers, \SimpleDiscord\SimpleDiscord $discord) {
		$this->headers = $headers;
		$this->discord = $discord;

		$this->gateway = new Resources\Gateway($this);
		$this->user = new Resources\User($this);
		$this->channel = new Resources\Channel($this);
		$this->guild = new Resources\Guild($this);
	}

	public function sendRequest(string $endpoint, array $opts=["http"=>[]]) {
		$opts["http"]["ignore_errors"] = true;

		if (!isset($opts["http"]["header"])) {
			$opts["http"]["header"] = [];
		}

		$opts["http"]["header"] = array_merge($opts["http"]["header"],$this->headers);

		if (!isset($opts["http"]["header"]["Content-Length"])) {
			$opts["http"]["header"]["Content-Length"] = isset($opts["http"]["content"]) ? strlen($opts["http"]["content"]) : 0;
		}

		if (!isset($opts["http"]["header"]["Content-Type"])) {
			$opts["http"]["header"]['Content-Type'] = 'application/json';
		}

		$headers = "";

		foreach ($opts["http"]["header"] as $key => $value) {
			$headers .= $key.": ".$value."\r\n";
		}

		$opts["http"]["header"] = $headers;

		$response = file_get_contents(self::BASE_URI.$endpoint, false, stream_context_create($opts));

		return json_decode($response);
	}
}