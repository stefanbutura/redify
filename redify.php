<?php

require_once __DIR__ . '/vendor/autoload.php';

$redify = new \Redify\App\Redify();
$redify->readTimeEntries();
