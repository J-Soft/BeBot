<?php

namespace SimpleDiscord;

class SimpleDiscord {
	public const VERSION = "0.0.2";
	public const LONG_VERSION = 'SimpleDiscord/v'.self::VERSION.' SimpleDiscord (https://github.com/smileytechguy/SimpleDiscord, v'.self::VERSION.')';

	private $params;
	// token - token of the discord bot
	// debug - level from 0 (none) to 3 (most verbose) of debug information
	public $restClient;
	private $socket;

	public $user;

	private $eventHandlers = [
		"ALL" => []
	];

	public $guilds = [];

	public function __construct(array $params) {		
		if (!isset($params["token"])) {
			throw new \InvalidArgumentException("No token provided!  Token should be provided as a parameter with key \"token\".");
		}

		$params["debug"] = (isset($params["debug"]) &&
			$params["debug"] <= 3 && $params["debug"] >= -1)
			? $params["debug"]
			: 1;

		$this->params = (object)$params;

		$this->log(self::LONG_VERSION, 0);

		$this->registerHandler("READY", [$this, 'handleReady']);
		$this->registerHandler("CHANNEL_CREATE", [$this, 'handleChannelCreate']);
		$this->registerHandler("CHANNEL_UPDATE", [$this, 'handleChannelUpdate']);
		$this->registerHandler("CHANNEL_DELETE", [$this, 'handleChannelDelete']);
		$this->registerHandler("CHANNEL_PINS_UPDATE", null);
		$this->registerHandler("GUILD_CREATE", [$this, 'handleGuildCreate']);
		$this->registerHandler("GUILD_UPDATE", [$this, 'handleGuildUpdate']);
		$this->registerHandler("GUILD_DELETE", [$this, 'handleGuildDelete']);
		$this->registerHandler("GUILD_BAN_ADD", null);
		$this->registerHandler("GUILD_BAN_REMOVE", null);
		$this->registerHandler("GUILD_EMOJIS_UPDATE", null);
		$this->registerHandler("GUILD_INTEGRATIONS_UPDATE", null);
		$this->registerHandler("GUILD_MEMBER_ADD", [$this, 'handleGuildMemberAdd']);
		$this->registerHandler("GUILD_MEMBER_REMOVE", [$this, 'handleGuildMemberRemove']);
		$this->registerHandler("GUILD_MEMBER_UPDATE", [$this, 'handleGuildMemberUpdate']);
		$this->registerHandler("GUILD_MEMBERS_CHUNK", [$this, 'handleGuildMembersChunk']);
		$this->registerHandler("GUILD_ROLE_CREATE", null);
		$this->registerHandler("GUILD_ROLE_UPDATE", null);
		$this->registerHandler("GUILD_ROLE_DELETE", null);
		$this->registerHandler("MESSAGE_CREATE", null);
		$this->registerHandler("MESSAGE_UPDATE", null);
		$this->registerHandler("MESSAGE_DELETE", null);
		$this->registerHandler("MESSAGE_DELETE_BULK", null);
		$this->registerHandler("MESSAGE_REACTION_ADD", null);
		$this->registerHandler("MESSAGE_REACTION_REMOVE", null);
		$this->registerHandler("MESSAGE_REACTION_REMOVE_ALL", null);
		$this->registerHandler("PRESENCE_UPDATE", null);
		$this->registerHandler("TYPING_START", null);
		$this->registerHandler("USER_UPDATE", '\var_dump');
		$this->registerHandler("VOICE_STATUS_UPDATE", null);
		$this->registerHandler("VOICE_SERVER_UPDATE", null);
		$this->registerHandler("WEBHOOKS_UPDATE", null);

		$this->log("Initializing REST Client", 2);

		$this->restClient = new \SimpleDiscord\RestClient\RestClient([
			'Authorization' => $this->params->token,
			'User-Agent' => self::LONG_VERSION
		], $this);

		$this->user = $this->restClient->user->getUser();
		if(isset($this->user->username)&&isset($this->user->discriminator)) {
			$this->log("Authenticated as @".$this->user->username."#".$this->user->discriminator, 1);
		}
	}

	public function run() {
		$this->log("Creating websocket", 1);
		$this->socket = new \SimpleDiscord\DiscordSocket\DiscordSocket($this);
		$this->socket->start();
	}
	
	public function ping() {
		$this->log("Pinging websocket", 1);
		$this->socket = new \SimpleDiscord\DiscordSocket\DiscordSocket($this);
		$this->socket->getSocket()->close();
	}	

	public function quit() {
		$this->socket->getSocket()->close();
		$this->log("Exiting", 0);
		die();
	}

	public function log(string $in, int $requiredLevel=1) {
		if ($this->params->debug >= $requiredLevel) {
			echo date('Y-m-d H:i:s')." ".$in."\n";
		}
	}

	public function getDebugLevel() : int {
		return $this->params->debug;
	}

	public function getToken() : string {
		preg_match('/[^ ]*$/', $this->params->token, $out);
		return $out[0];
	}

	public function getSocket() : \SimpleDiscord\DiscordSocket\DiscordSocket {
		return $this->socket;
	}

	public function getRestClient() : \SimpleDiscord\RestClient\RestClient {
		return $this->restClient;
	}

	public function getSessionId() : string {
		return $this->sessionId;
	}

	public function registerHandler($event, $handler) {
		if (!isset($this->eventHandlers[$event])) {
			$this->eventHandlers[$event] = [];
		}
		if (is_null($handler)) {
			return;
		}
		$this->eventHandlers[$event][] = $handler;
	}

	private function handleReady($event, $data, $discord) {
		$this->sessionId = $data->session_id;
		foreach ($data->guilds as $guild) {
			$this->guilds[$guild->id] = $guild;
		}
	}

	private function handleChannelCreate($event, $data, $discord) {
		$this->channels[$data->id] = $data;
	}
	private function handleChannelUpdate($event, $data, $discord) {
		$this->channels[$data->id] = $data;
	}
	private function handleChannelDelete($event, $data, $discord) {
		unset($this->channels[$data->id]);
	}

	private function handleGuildCreate($event, $data, $discord) {
		$this->guilds[$data->id] = $data;
	}

	private function handleGuildUpdate($event, $data, $discord) {
		$this->guilds[$data->id] = $data;
	}

	private function handleGuildDelete($event, $data, $discord) {
		unset($this->guilds[$data->id]);
	}

	private function handleGuildMemberAdd($event, $data, $discord) {
		$this->guilds[$data->guild_id]->members[] = $data;
		$this->guilds[$data->guild_id]->member_count++;
	}

	private function handleGuildMemberRemove($event, $data, $discord) {
		foreach ($this->guilds[$data->guild_id]->members as $value) {
			if ($value->user->id == $data->user->id) {
				$this->guilds[$data->guild_id]->members = array_filter(
					$this->guilds[$data->guild_id]->members, 
					function ($in) use ($value) { return $in != $value; }
				);
				break;
			}
		}

		$this->guilds[$data->guild_id]->member_count--;
	}

	private function handleGuildMemberUpdate($event, $data, $discord) {
		foreach ($this->guilds[$data->guild_id]->members as &$value) {
			if ($value->user->id == $data->user->id) {
				$value->user = $data->user;
				$value->roles = $data->roles;
				$value->nick = $data->nick;
				break;
			}
		}
	}

	private function handleGuildMembersChunk($event, $data, $discord) {
		$this->guilds[$data->guild_id]->members = array_merge($this->guilds[$data->guild_id]->members, $data->members);
	}

	public function dispatch($event, $data) {
		foreach ($this->eventHandlers["ALL"] as $handler) {
			call_user_func($handler, $event, $data, $this);
		}
		if (!isset($this->eventHandlers[$event])) {
			$this->log("Unhandled event: ".$event, 0);
		} else {
			foreach ($this->eventHandlers[$event] as $handler) {
				call_user_func($handler, $event, $data, $this);
			}
		}
	}

	public function getGuildIdFromChannel(string $id) : string {
		foreach ($this->guilds as $guild) {
			if (in_array($id, array_map(function($in) {return $in->id;}, $guild->channels))) {
				return $guild->id;
			}
		}
	}
}
