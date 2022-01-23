<?php

namespace WebXID\PHPUnitSandbox;

require_once __DIR__ . '/../bootstrap.php';

try {
	foreach ($_SERVER['argv'] as $argv) {
		if (substr($argv, 0, strlen(UnitSandbox::CLI_ARGV)) == UnitSandbox::CLI_ARGV) {
			$response = UnitSandbox::recoveryInstance(substr($argv, strlen(UnitSandbox::CLI_ARGV) + 1))
				->registerMockedData()
				->registerAutoloader()
				->registerSpyData()
				->run();

			break;
		}
	}
} catch (\Exception $e) {
	$response = [
		'status' => false,
		'throwed' => get_class($e),
		'message' => $e->getMessage(),
		'trace' => $e->getFile() . ':' . $e->getLine(),
	];
} catch (\Error $e) {
	$response = [
		'status' => 'throwed',
		'throw_class' => get_class($e),
		'message' => $e->getMessage(),
		'trace' => $e->getFile() . ':' . $e->getLine(),
	];
}

echo json_encode($response);