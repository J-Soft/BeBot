<?php
// This will autogenerate the php file from the .proto file
require_once('protocolbuf/parser/pb_parser.php');

$test = new PBParser();
$test->parse('./Endpoints.proto');
var_dump('File parsing done!');
?>
