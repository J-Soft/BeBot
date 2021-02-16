<?php

// Debug level will be 3

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord([
	"token" => file_get_contents("tests/token.txt"),
	"debug" => 3
]);
echo "Debug level: ".$discord->getDebugLevel()."\n";
