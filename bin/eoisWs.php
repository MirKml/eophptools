<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . "/eois-config.php")) {
   $config = require __DIR__ . "/eois-config.php";
} else {
    $config = require getcwd() . "/eois-config.php";
}
$cli = new MirKml\EO\EoisWsCli($config);
$cli->execute();
exit($cli->hasErrors() ? 0 : 1);
