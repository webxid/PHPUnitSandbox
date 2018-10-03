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

namespace WebPackage\PHPUnitSandbox\Helper;

use WebPackage\PHPUnitSandbox\UnitSandbox;

/**
 * Class MockClass
 *
 * @package WebPackage\PHPUnitSandbox\Helper
 */
class MockClass
{
	private $is_spy = false;
	private $spy_namespace = '';

	/**
	 * @var array
	 * [
	 * 		method_name => [
	 * 			'call_type' => string,
	 * 			'return' => string | \Closure
	 * 		],
	 * 		...
	 * ]
	 */
	private $methods = [];

	/**
	 * @var array
	 * [
	 * 		name => mixed,
	 * 		...
	 * ]
	 */
	private $consts = [];

	/**
	 * @var array
	 * [
	 * 		name => mixed,
	 * 		...
	 * ]
	 */
	private $static_properties = [];

	/**
	 * @var array
	 * [
	 * 		name => mixed,
	 * 		...
	 * ]
	 */
	private $properties = [];

	#region Magic methods

	public function __construct() {}

	#endregion

	#region Setters

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

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return MockClass
	 */
	public function defineConst($name, $value)
	{
		if (is_array($value) || is_object($value)) {
			throw new \InvalidArgumentException('Invalid $value');
		}

		return $this->define($name, $value, UnitSandbox::VAR_TYPE_CONST);
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return MockClass
	 */
	public function defineStaticProperty($name, $value)
	{
		return $this->define($name, $value, UnitSandbox::VAR_TYPE_STATIC_PROPERTY);
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return MockClass
	 */
	public function defineProperty($name, $value)
	{
		return $this->define($name, $value, UnitSandbox::VAR_TYPE_PROPERTY);
	}

	/**
	 * @param bool $is_spy
	 *
	 * @return $this
	 */
	public function setSpyStatus($is_spy)
	{
		$this->is_spy = (bool) $is_spy;

		return $this;
	}

	/**
	 * @param string $spy_namespace
	 *
	 * @return $this
	 */
	public function setSpyNamespace($spy_namespace)
	{
		if (empty($spy_namespace) || !is_string($spy_namespace)) {
			throw new \InvalidArgumentException('Invalid $sandbox_namespace');
		}

		$this->spy_namespace = $spy_namespace;

		return $this;
	}

	#endregion

	#region Is Condition methods

	/**
	 * Checks, is current mocked class Spy or not
	 *
	 * @return bool
	 */
	public function isSpy()
	{
		return $this->is_spy;
	}

	#endregion

	#region Getters

	/**
	 * Returns list of collected mocked up methods of current class
	 *
	 * @return array - @see $this->methods
	 */
	public function getMethods()
	{
		return $this->methods;
	}

	/**
	 * @return array
	 * [
	 * 		'consts' => arraay,
	 * 		'static_properties' => array,
	 * 		'properties' => array,
	 * ]
	 */
	public function getVars()
	{
		return [
			'consts' => $this->consts,
			'static_properties' => $this->static_properties,
			'properties' => $this->properties,
		];
	}

	public function getSpyNamespace()
	{
		return $this->spy_namespace;
	}

	#endregion

	#region Helpers

	private function mockup($method_name, $return, $calling_type = UnitSandbox::CALL_TYPE_OBJECT)
	{
		// Method name validation
		if (empty($method_name) || !is_string($method_name)) {
			throw new \InvalidArgumentException('Invalid $method_name');
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

	private function define($name, $value, $var_type)
	{
		if (empty($name) || !is_string($name)) {
			throw new \InvalidArgumentException('Invalid $name');
		}

		switch ($var_type) {
			case UnitSandbox::VAR_TYPE_CONST:
				$this->consts[$name] = $value;

				break;

			case UnitSandbox::VAR_TYPE_STATIC_PROPERTY:
				$this->static_properties[$name] = $value;

				break;

			case UnitSandbox::VAR_TYPE_PROPERTY:
				$this->properties[$name] = $value;

				break;

			default:
				throw new \InvalidArgumentException('Invalid $type');
		}

		return $this;
	}

	#endregion
}
