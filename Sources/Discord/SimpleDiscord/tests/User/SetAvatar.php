<?php

// will set the avatar to "avatar.gif"

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord([
	"token" => file_get_contents("tests/token.txt"),
	"debug" => 3
]);

$client = $discord->getRestClient();

var_dump($client->user->getUser());

echo "Setting avatar to \"avatar.gif\"\n";

$client->user->setAvarar(__DIR__."/avatar.gif");

var_dump($client->user->getUser());
