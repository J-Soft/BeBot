<?php

// Should throw an InvalidArgumentException "No token provided!  Token should be provided as a parameter with key "token"."

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord(["debug" => 3]);
