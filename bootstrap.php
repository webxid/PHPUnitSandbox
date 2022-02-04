<?php

$loader = include __DIR__ . '/../../autoload.php'; // vendor's autoloader
//$loader = include __DIR__ . '/vendor/autoload.php'; // use this row instead the previous one, if you run the unit tests from the lib folder
$loader->addPsr4('WebXID\PHPUnitSandbox\\', __DIR__ . '/src/', true);
