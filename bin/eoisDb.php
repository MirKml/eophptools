<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . "/eois-config.php")) {
   $config = require __DIR__ . "/eois-config.php";
} else {
    $config = require getcwd() . "/eois-config.php";
}
$cli = new MirKml\EO\EoisDbCli($config);
$cli->execute();
if (!$cli->hasErrors()) {
    exit(0);
}

echo implode("\n", $cli->getErrors());
exit(1);
