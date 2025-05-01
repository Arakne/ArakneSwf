<?php

require_once __DIR__.'/../../../src/Console/ExtractOptions.php';

$options = \Arakne\Swf\Console\ExtractOptions::createFromCli();

echo json_encode($options);
