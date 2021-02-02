<?php

namespace SimpleDiscord\RestClient\Resources;

class Channel extends BaseResource {
	public function sendMessage($channelId, $content, $embed=null) {
		$contents = ["content" => $content];
		if (!is_null($embed)) {
			$contents["embed"] = $embed;
		}
		return $this->client->sendRequest(
			"channels/{$channelId}/messages",
			[
				"http" => [
					"method" => "POST",
					"content" => json_encode($contents)
				]
			]
		);
	}

	public function sendMessageWithFile($channelId, $content, $filename) {
		$contents = ["content" => $content];
		$boundary = "------".microtime(true);
		return $this->client->sendRequest(
			"channels/{$channelId}/messages",
			[
				"http" => [
					"method" => "POST",
					"header" => [
						"Content-Type" => "multipart/form-data;boundary=".$boundary
					],
					"content" => implode("\r\n", [
						"--{$boundary}",
						"Content-Disposition: form-data; name=\"payload_json\"",
						"",
						json_encode($contents),
						"--{$boundary}",
						"Content-Disposition: form-data; name=\"file\"; filename=\"".pathinfo($filename, PATHINFO_BASENAME)."\"",
						"Content-Type: ".mime_content_type($filename),
						"",
						file_get_contents($filename),
						"--{$boundary}--",
						""
					])
				]
			]
		);
	}

	public function sendMessageWithImagick($channelId, $content, \Imagick $im) {
		$contents = ["content" => $content];
		$boundary = "------".microtime(true);
		return $this->client->sendRequest(
			"channels/{$channelId}/messages",
			[
				"http" => [
					"method" => "POST",
					"header" => [
						"Content-Type" => "multipart/form-data;boundary=".$boundary
					],
					"content" => implode("\r\n", [
						"--{$boundary}",
						"Content-Disposition: form-data; name=\"payload_json\"",
						"",
						json_encode($contents),
						"--{$boundary}",
						"Content-Disposition: form-data; name=\"file\"; filename=\"image.png\"",
						"Content-Type: ".$im->getImageFormat(),
						"",
						$im,
						"--{$boundary}--",
						""
					])
				]
			]
		);
	}

	public function getMessage($channelId, $messageId) {
		return $this->client->sendRequest(
			"channels/{$channelId}/messages/{$messageId}"
		);
	}

	public function deleteMessage($channelId, $messageId) {
		return $this->client->sendRequest(
			"channels/{$channelId}/messages/{$messageId}",
			[
				"http" => [
					"method" => "DELETE"
				]
			]
		);
	}

	public function addReaction($channelId, $messageId, $emote) {
		return $this->client->sendRequest(
			"channels/{$channelId}/messages/{$messageId}/reactions/{$emote}/@me",
			[
				"http" => [
					"method" => "PUT"
				]
			]
		);
	}

	public function getReactions($channelId, $messageId, $emote) {
		return $this->client->sendRequest(
			"channels/{$channelId}/messages/{$messageId}/reactions/{$emote}"
		);
	}
}
