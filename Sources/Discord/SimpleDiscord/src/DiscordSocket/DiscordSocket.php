<?php

namespace SimpleDiscord\DiscordSocket;

class DiscordSocket {
	private static $gatewayURL = null;

	private $discord;
	private $socket;

	private $sessionId;

	private $lastHeartbeat,$heartbeatInterval=PHP_INT_MAX;

	private $lastFrame=null;

	private const CURRENT_GATEWAY_VERSION = "9";

	public function __construct(\SimpleDiscord\SimpleDiscord $discord) {
		$this->discord = $discord;

		if (self::$gatewayURL === null) {
			self::$gatewayURL = $this->discord->getRestClient()->gateway->getGateway();
			$this->discord->log("Getting Gateway URI: ".self::$gatewayURL, 3);
		} else {
			$this->discord->log("Gateway URI cached: ".self::$gatewayURL, 3);
		}

		$this->socket = new \SimpleDiscord\DiscordSocket\BetterClient(self::$gatewayURL."?v=".self::CURRENT_GATEWAY_VERSION."&encoding=json", [
			"timeout" => 300
		]);

		$this->socket->connectIfNotConnected();

		$this->discord->log("Identifying to websocket", 3);
		
		$this->identify();

		$this->discord->log("Websocket initialized.", 1);
	}

	public function start() {
		$this->discord->log("Listening on WS", 1);

		$this->run();
	}

	protected function identify() {
		$this->socket->send(
			json_encode([
				'op' => 2,
				'd' => [
					'token' => $this->discord->getToken(),
					'properties' => [
						'$os' => php_uname("s")." ".php_uname("r"),
						'$browser' => \SimpleDiscord\SimpleDiscord::LONG_VERSION,
						'$device' => \SimpleDiscord\SimpleDiscord::LONG_VERSION
					],
					'compress' => true
				]
			])
		);
	}

	protected function reconnect() {
		$this->socket->send(
			json_encode([
				'op' => 6,
				'd' => [
					'token' => $this->discord->getToken(),
					'session_id' => $this->discord->getSessionId(),
					'seq' => $this->lastFrame
				]
			])
		);
	}

	public function run() {
		$this->parseResponse($this->socket->receive()); // initial
		$this->lastHeartbeat = microtime(true);
		while (true) {
			try {
				if (microtime(true)-$this->lastHeartbeat >= ($this->heartbeatInterval/1000) ||
					@stream_get_meta_data($this->socket->getSocket())["timed_out"]) {
					$timeTillHeartbeat = max((int)(($this->heartbeatInterval/1000)-microtime(true)+$this->lastHeartbeat-1),1);
					$this->socket->setTimeout($timeTillHeartbeat);
					$this->sendHeartbeat();
				}
				// heartbeat "timer"
				// we can do this because the gateway will always resume on its end.  Therefore if it "times out" we know that our "timer" has elapsed
				$timeTillHeartbeat = max((int)(($this->heartbeatInterval/1000)-microtime(true)+$this->lastHeartbeat-1),1);
				$this->socket->setTimeout($timeTillHeartbeat);

				$this->parseResponse($this->socket->receive());
			} catch (\Exception $e) {
				$this->discord->log("WS ERROR - RECONNECTING AFTER 30 SECONDS: ".serialize($e), 0);

				$this->socket->close();

				sleep(30);

				$this->socket = new \SimpleDiscord\DiscordSocket\BetterClient(self::$gatewayURL."?v=".self::CURRENT_GATEWAY_VERSION."&encoding=json", [
					"timeout" => 300
				]);

				$this->reconnect();
			}
		}
	}

	protected function sendHeartbeat() {
		$this->lastHeartbeat = microtime(true);
		$this->discord->log("Sending hearbeat", 3);
		$this->socket->send(json_encode([
			"op" => 1,
			"d" => $this->lastFrame
		]));
	}

	public function setStatus(array $status) {
		$this->discord->log("Setting status to ".serialize($status), 3);
		$this->socket->send(json_encode([
			"op" => 3,
			"d" => $status
		]));
	}

	public function requestGuildMembers(string $id, string $query="", int $limit=0) {
		$this->discord->log("Requesting members with options ".serialize([$id, $query, $limit]), 3);
		$this->discord->guilds[$id]->members = [];
		$this->socket->send(json_encode([
			"op" => 8,
			"d" => [
				"guild_id" => $id,
				"query" => $query,
				"limit" => $limit
			]
		]));
	}

	public function parseResponse(string $in) {
		if ($in == "TIMED OUT") {
			return;
		}

		if ($this->socket->getLastOpcode() == "close") {
			switch (strtolower(substr($in, 0, 11))) {
				case "unknown err":
					$err = "4000 - unknown error";
					break;
				case "unknown opc":
					$err = "4001 - unknown opcode";
					break;
				case "decode erro":
					$err = "4002 - decode error";
					break;
				case "not authent":
					$err = "4003 - not authenticated";
					break;
				case "authenticat":
					$err = "4004 - authentication failed";
					$this->discord->log("Invalid authentication", -1);
					die("Invalid authentication\n");
					break;
				case "already aut":
					$err = "4005 - already authenticated";
					break;
				case "invalid seq":
					$err = "4007 - invalid seq";
					break;
				case "rate limite":
					$err = "4008 - rate limited";
					break;
				case "session tim":
					$err = "4009 - session timeout";
					$this->reconnect();
					break;
				case "invalid sha":
					$err = "4010 - invalid shard";
					break;
				case "sharding re":
					$err = "4011 - sharding required";
					break;
				default:
					$err = "Undocumented error - ".$in;
					var_dump($err);
			}
			$this->discord->log("Socket error: ".$err, 0);
			return;
		}

		if (substr($in, 0, 1) != "{" && strlen($in) !== 0) {
			$decoded = zlib_decode($in);
			if ($decoded === false) {
				$this->discord->log("Recieved an unknown response from gateway ".$in, 1);
				return;
			}
			$in = $decoded;
		}

		$response = json_decode($in);

		if (!is_null($response)) {
			switch ($response->op) {
				case 0: // Dispatch
					$this->discord->log("Received gateway dispatch #".$response->s." ".$response->t, 3);
					$this->lastFrame = $response->s;
					$this->discord->dispatch($response->t, $response->d);
					break;
				case 1: // Heartbeat
					$this->discord->log("Heartbeat requested", 3);
					$this->sendHeartbeat();
					break;
				case 7: // Reconnect
					$this->discord->log("Reconnect requested", 2);

					$this->discord->log("Creating websocket", 2);

					$this->socket = new \SimpleDiscord\DiscordSocket\BetterClient(self::$gatewayURL."?v=".self::CURRENT_GATEWAY_VERSION."&encoding=json", [
						"timeout" => 300
					]);

					$this->socket->connectIfNotConnected();

					$this->discord->log("Reconnecting to websocket", 3);
					
					$this->reconnect();

					$this->discord->log("Websocket initialized.  Listening", 1);
					break;
				case 9: // Invalid Session
					$this->discord->log("Invalid session provided", 2);
					sleep(random_int(1, 5));
					$this->identify();
					break;
				case 10: // Hello
					$this->discord->log("Received hello", 3);
					$this->heartbeatInterval = $response->d->heartbeat_interval;
					$this->sendHeartbeat();
					break;
				case 11: // Heartbeat ACK
					$this->discord->log("Heartbeat acknowledged", 3);
					$this->discord->dispatch("HEARTBEAT", (object)[]);
					break;
				default:
					$this->discord->log("UNKNOWN GATEWAY OP CODE ".$response->op.".  Full event: ".$in, 0);
					break;
			}
		}

		return $response;
	}

	public function getSocket() {
		return $this->socket;
	}
}
