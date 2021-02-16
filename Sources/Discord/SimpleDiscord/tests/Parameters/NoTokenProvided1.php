<?php

// Should throw an InvalidArgumentException "No token provided!"

require_once "autoload.php";

$discord = new \SimpleDiscord\SimpleDiscord([]);
