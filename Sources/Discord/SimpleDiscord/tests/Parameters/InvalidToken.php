<?php

// Should throw an error and quit saying "Invalid authentication" or something of the like

require_once "autoload.php";

$discord = (new \SimpleDiscord\SimpleDiscord(["token" => "not-a-token"]))->run();
