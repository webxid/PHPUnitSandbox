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

namespace WebPachage\PHPUnitSandbox\Helper;

use WebPachage\PHPUnitSandbox\UnitSandbox;

/**
 * Class MockClass
 *
 * @package WebPachage\PHPUnitSandbox\Helper
 */
class MockClass
{
	/**
	 * @var array
	 * [
	 * 		method_name => [
	 * 			'call_type' => string,
	 * 			'return' => string | \Closure
	 * 		],
	 * ]
	 */
	private $methods = [];

	public function __construct() {}

	/**
	 * Mocks up static method
	 *
	 * @param string $method_name
	 * @param mixed $return
	 *
	 * @return MockClass
	 */
	public function mockStaticMethod($method_name, $return) {
		return $this->mockup($method_name, $return, UnitSandbox::CALL_TYPE_STATIC);
	}

	/**
	 * Mocks up object method
	 *
	 * @param string $method_name
	 * @param mixed $return
	 *
	 * @return MockClass
	 */
	public function mockMethod($method_name, $return) {
		return $this->mockup($method_name, $return, UnitSandbox::CALL_TYPE_OBJECT);
	}

	private function mockup($method_name, $return, $calling_type = UnitSandbox::CALL_TYPE_OBJECT)
	{
		// Method name validation
		if (empty($method_name) || !is_string($method_name)) {
			throw new \InvalidArgumentException('Invalid $method_name');
		}

		if (is_object($return)) {
			throw new \InvalidArgumentException('Parameter $return cannot be object');
		}

		// CALL_TYPE validation
		switch ($calling_type) {
			case UnitSandbox::CALL_TYPE_STATIC:
			case UnitSandbox::CALL_TYPE_OBJECT:
				break;

			default:
				throw new \InvalidArgumentException('Invalid $calling_type');
		}

		$this->methods[$method_name] = [
			'call_type' => $calling_type,
			'return' => $return,
		];

		return $this;
	}

	/**
	 * Returns list of collected mocked up methods of current class
	 *
	 * @return array - @see $this->methods
	 */
	public function getMethods()
	{
		return $this->methods;
	}
}
