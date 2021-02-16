<?php

// should print a lad by the name of simstart

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord([
	"token" => file_get_contents("tests/token.txt"),
	"debug" => 3
]);

$client = $discord->getRestClient();

echo $client->user->getUser("259400759395876864")."\n";
