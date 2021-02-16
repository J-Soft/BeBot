<?php

namespace SimpleDiscord\RestClient\Resources;

class Gateway extends BaseResource {
	public function getGateway() : string {
		return $this->client->sendRequest("gateway")->url;
	}
}