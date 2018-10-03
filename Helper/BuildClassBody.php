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
 * Class BuildClassBody
 *
 * @package WebPackage\PHPUnitSandbox\Helper
 *
 * @property array $methods
 * @property string $spy_namespace
 * @property array $consts
 * @property array $static_properties
 * @property array $properties
 * @property bool $is_spy
 */
class BuildClassBody
{
	private $methods = [];
	private $spy_namespace = '';
	private $consts = [];
	private $static_properties = [];
	private $properties = [];
	private $is_spy = false;
	private $class_name = '';
	private $methods_namespace = '';
	private $class_namespace = '';

	private static $writable_properties = [
		'methods' => true,
		'spy_namespace' => true,
		'consts' => true,
		'static_properties' => true,
		'properties' => true,
		'is_spy' => true,
	];

	#region Magic methods

	public function __set($property_name, $value)
	{
		if (!isset(self::$writable_properties[$property_name])) {
			throw new \InvalidArgumentException("Property \$this->{$property_name} is not exist");
		}

		$this->$property_name = $value;
	}

	#endregoin

	#region Builders

	public static function init($class_name)
	{
		if (empty($class_name) || !is_string($class_name)) {
			throw new \InvalidArgumentException('Invalid $class_name');
		}

		$object = new static();

		$namespace = explode('\\', $class_name);
		$count = count($namespace) - 1;
		$class_name = $namespace[$count];

		unset($namespace[$count]);

		$object->methods_namespace = implode('\\', $namespace);
		$object->class_name = $class_name;

		return $object;
	}

	#endregion

	#region Object methods

	public function execute()
	{
		$this->defineClassNamespace();

		//Build Methods body
		$methods = '';

		foreach ($this->methods as $method_name => $method_data) {
			$call_type = ($method_data['call_type'] == UnitSandbox::CALL_TYPE_STATIC ? UnitSandbox::CALL_TYPE_STATIC : '');

			$return = '$this';

			if ($call_type === UnitSandbox::CALL_TYPE_STATIC) {
				$return = 'new self()';
			}

			$methods .= $this->tplMethod([
				'call_type' => $call_type,
				'method_name' => $method_name,
				'return' => $return,
			]);
		}

		return $this->buildClassBody([
			'methods' => $methods,
			'consts' => $this->tplConsts(),
			'static_properties' => $this->tplStaticProperties(),
			'properties' => $this->tplProperties(),
		]);
	}

	private function defineClassNamespace()
	{
		$namespace = [];

		foreach ([$this->spy_namespace, $this->methods_namespace] as $nspace) {
			if (!empty($nspace)) {
				$namespace[] = $nspace;
			}
		}

		$this->class_namespace = implode('\\', $namespace);
	}

	#endregion

	#region Helpers

	private function tplMethod(array $data)
	{
		$class_name = (!empty($this->methods_namespace) ? $this->methods_namespace . '\\' : '') . $this->class_name;

		return "
	public {$data['call_type']} function {$data['method_name']}()
	{
		\$result = UnitSandbox::callMethod('{$class_name}', '{$data['method_name']}');
		
		if (\$result === UnitSandbox::SELF_INSTANCE) {
			return {$data['return']};
		} elseif (\$result instanceof \Closure) {
			return \$result({$data['return']}, func_get_args());
		}
		
		return \$result;
	}";

	}

	private function tplConsts()
	{
		$consts = '';

		foreach ($this->consts as $const_name => $const_val) {
			$const_val = $this->getValueAsString($const_val, UnitSandbox::VAR_TYPE_CONST);
			$consts .= "	const {$const_name} = {$const_val};\n";
		}

		return $consts;
	}

	private function tplStaticProperties()
	{
		$static_properties = '';

		foreach ($this->static_properties as $property_name => $property_val) {
			$property_val = $this->getValueAsString($property_val, UnitSandbox::VAR_TYPE_STATIC_PROPERTY);
			$static_properties .= "	public static \${$property_name} = {$property_val};\n";
		}

		return $static_properties;
	}

	private function tplProperties()
	{
		$properties = '';

		foreach ($this->properties as $property_name => $property_val) {
			$property_val = $this->getValueAsString($property_val, UnitSandbox::VAR_TYPE_PROPERTY);
			$properties .= "	public \${$property_name} = {$property_val};\n";
		}

		return $properties;
	}


	private function buildClassBody(array $data)
	{
		$namespace = (!empty($this->class_namespace) ? "namespace {$this->class_namespace};\n" : '');

		$extends = ($this->is_spy ? ' extends ' . $this->methods_namespace . '\\' . $this->class_name : '');

		return "{$namespace}
use WebPackage\PHPUnitSandbox\UnitSandbox;

class {$this->class_name} {$extends}
{
	{$data['consts']}
	{$data['static_properties']}
	{$data['properties']}

	{$data['methods']}
}
";
	}

	private function getValueAsString($value, $var_type)
	{
		switch ($var_type) {
			case UnitSandbox::VAR_TYPE_CONST:
				if (is_string($value)) {
					return "'{$value}'";
				} elseif (is_numeric($value)) {
					return "{$value}";
				} elseif (is_bool($value)) {
					return ($value ? 'true' : 'false');
				} elseif (is_null($value)) {
					return 'null';
				}

				throw new \InvalidArgumentException("Invalid constant value. Class name '{$this->class_name}'");

			case UnitSandbox::VAR_TYPE_STATIC_PROPERTY:
			case UnitSandbox::VAR_TYPE_PROPERTY:
				if (is_string($value)) {
					return "'{$value}'";
				} elseif (is_numeric($value)) {
					return "{$value}";
				} elseif (is_bool($value)) {
					return ($value ? 'true' : 'false');
				} elseif (is_null($value)) {
					return 'null';
				} elseif (is_array($value)) {
					$result = "[\n";

					foreach ($value as $key => $val) {
						$result .= $this->getValueAsString($val, UnitSandbox::VAR_TYPE_CONST) . " => " . $this->getValueAsString($val, $var_type) . ",\n";
					}

					return $result .']';
				}

				throw new \InvalidArgumentException("Invalid property value. Class name '{$this->class_name}'");

			default:
				throw new \InvalidArgumentException('Invalid $type');
		}
	}

	#endregion
}
