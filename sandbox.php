<?php
/**
 * This file is part of PHPUnitSandbox package
 *
 * @package PHPUnitSandbox
 * @source https://github.com/webpackage-pro/PHPUnitSandbox
 *
 * @author Pavlo Matsura <webxid@ukr.net>
 * @link https://webpackage.pro
 *
 * @copyright 2018 (c) Pavlo Matsura
 * @license For the full copyright and license information,
 *          please view the LICENSE file that was distributed with this source code
 */

namespace WebPackage\PHPUnitSandbox;

require_once __DIR__ . '/autoloader.php';

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
