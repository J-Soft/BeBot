<?php

// will print the authenticated user (uses REST api)

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord([
	"token" => file_get_contents("tests/token.txt"),
	"debug" => 3
]);

$client = $discord->getRestClient();

echo var_dump($client->user->getUser())."\n";
