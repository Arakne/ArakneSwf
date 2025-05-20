<?php

require_once __DIR__.'/../../../src/Console/ExtractOptions.php';
require_once __DIR__.'/../../../src/Extractor/Drawer/Converter/DrawableFormater.php';
require_once __DIR__.'/../../../src/Extractor/Drawer/Converter/ImageFormat.php';
require_once __DIR__.'/../../../src/Extractor/Drawer/Converter/ImageResizerInterface.php';
require_once __DIR__.'/../../../src/Extractor/Drawer/Converter/FitSizeResizer.php';

$options = \Arakne\Swf\Console\ExtractOptions::createFromCli();

echo json_encode($options);
