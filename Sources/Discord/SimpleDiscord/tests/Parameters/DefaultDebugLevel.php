<?php

// Debug level will be 1

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord([
	"token" => file_get_contents("tests/token.txt")
]);
echo "Debug level: ".$discord->getDebugLevel()."\n";
