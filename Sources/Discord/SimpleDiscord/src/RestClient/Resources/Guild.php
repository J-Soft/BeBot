<?php

namespace SimpleDiscord\RestClient\Resources;

class Guild extends BaseResource {
	public function get($id) {
		return $this->client->sendRequest(
			"guilds/{$id}"
		);
	}

	public function getChannels($id) {
		return $this->client->sendRequest(
			"guilds/{$id}/channels"
		);
	}

	public function getMember($guildId, $userId) {
		return $this->client->sendRequest(
			"guilds/{$guildId}/members/{$userId}"
		);
	}

	public function getMembers($guildId) {
		$members = $this->client->sendRequest("guilds/{$guildId}/members?limit=1000");
		if (count($members) < 1000) {
			return $members;
		}		
		$nextMembers = $this->client->sendRequest("guilds/{$guildId}/members?limit=1000&after=".($members[count($members)-1]->id));
		while (count($nextMembers) != 0 && count($nextMembers) == 1000) {
			$members = array_merge($members, $nextMembers);
			$nextMembers = $this->client->sendRequest("guilds/{$guildId}/members?limit=1000&after=".($members[count($members)-1]->id));
		}
		$members = array_merge($members, $nextMembers);
		return $members;
	}
}
