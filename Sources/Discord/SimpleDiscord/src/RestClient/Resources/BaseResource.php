<?php

namespace SimpleDiscord\RestClient\Resources;

abstract class BaseResource {
	protected $client;

	public function __construct(\SimpleDiscord\RestClient\RestClient $client) {
		$this->client = $client;
	}
}
