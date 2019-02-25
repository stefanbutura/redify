<?php

require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(function($className) {
  $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
  include_once __DIR__ . '/class/' . $className . '.php';
});

$redify = new Redify();
$redify->readTimeEntries();
