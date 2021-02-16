<?php

namespace SimpleDiscord\RestClient\Resources;

class User extends BaseResource {
	public function getUser(string $id="@me") : \stdClass {
		$data = $this->client->sendRequest("users/".$id);

		return $data;
	}

	public function getDMs() : array {
		$data = $this->client->sendRequest("users/@me/channels");

		return $data;
	}

	public function createDM($id) : \stdClass {
		$data = $this->client->sendRequest(
			"users/@me/channels",
			[
				"http" => [
					"method" => "POST",
					"recepient_id" => $id
				]
			]
		);

		return $data;
	}

	public function setUsername(string $username) : \stdClass {
		$data = $this->client->sendRequest(
			"users/@me",
			[
				"http" => [
					"method" => "PATCH",
					"content" => json_encode([
						"username" => $username
					])
				]
			]
		);

		return $data;
	}

	public function setAvatar(string $file) : \stdClass {
		$data = $this->client->sendRequest(
			"users/@me",
			[
				"http" => [
					"method" => "PATCH",
					"content" => json_encode([
						"avatar" => 'data:image/'.pathinfo($file, PATHINFO_EXTENSION).';base64,'.base64_encode(file_get_contents($file))
					])
				]
			]
		);

		return $data;
	}
}
