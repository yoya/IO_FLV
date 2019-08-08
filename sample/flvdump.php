<?php

if (is_readable('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else {
    require_once 'IO/FLV.php';
}

$options = getopt("f:h");

function usage() {
    echo "Usage: php flvdump.php [-h] -f <flvfile>".PHP_EOL;
}

if ((isset($options['f']) === false) ||
    (is_readable($options['f']) === false)) {
    usage();
    exit(1);
}

$flvfile = $options['f'];
$flvdata = file_get_contents($flvfile);

$opts['hexdump'] = isset($options['h']);

$flv = new IO_FLV();
$flv->parse($flvdata);

$flv->dump($opts);

exit(0);
