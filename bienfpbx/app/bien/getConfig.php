<?php
require_once "config.inc.php";
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
print PBX_STAT_GROUPS;
?>
