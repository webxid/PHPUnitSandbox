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

namespace WebPachage\PHPUnitSandbox;

use Closure;
use WebPachage\PHPUnitSandbox\Helper\MockClass;
use WebPachage\PHPUnitSandbox\Opis\Closure\SerializableClosure;

/**
 * Class UniSandbox
 * @see https://docs.opis.io/closure/3.x/serialize.html
 *
 * @package Core
 *
 * @property array system_autoload_url
 */
class UnitSandbox
{
	const SANDBOX_FILE_ROUTE = __DIR__ . '/sandbox.php';
	const CLI_ARGV = '--instance';

	const SELF_INSTANCE = 'self() or $this';

	const CALL_TYPE_STATIC = 'static';
	const CALL_TYPE_OBJECT = '->';

	/**
	 * @var static
	 */
	static protected $instance;
	static protected $readable_properties = [];
	static protected $writable_properties = [
		'system_autoload_url' => true,
	];
	static private $debugMode = false;

	/** @var MockClass[] */
	private $mock_class_instance;

	/** @var array */
	private $sandbox_data = [
		/** @var Closure */
		'execute_this_code' => null,
		/** @var array	 */
		'system_autoload_url' => [],
		'mocked_classes' => [],
	];

	protected function __construct() {}

	public function __get($property_name)
	{
		if (isset(static::$readable_properties[$property_name])) {
			return $this->$property_name;
		}

		throw new \InvalidArgumentException("Property `{$property_name}` does not exist");
	}

	public function __isset($property_name)
	{
		if (isset(static::$readable_properties[$property_name])) {
			return isset($this->$property_name);
		}

		return false;
	}

	public function __set($property_name, $value)
	{
		if (!isset(static::$writable_properties[$property_name])) {
			throw new \InvalidArgumentException("Property `{$property_name}` does not exist");
		}

		switch ($property_name) {
			case 'system_autoload_url':
				$value = (array) $value;

				foreach ($value as $route) {
					if (!is_string($route)) {
						throw new \InvalidArgumentException('UniSandbox::system_autoload_url has to be string');
					}

					if (!is_file($route)) {
						throw new \InvalidArgumentException("There is no file: '{$route}'");
					}

					if (substr($route, -4) !== '.php') {
						throw new \InvalidArgumentException("Autoloader has to be .php file");
					}

					$this->sandbox_data['system_autoload_url'][] = $route;
				}

				return $this->sandbox_data['system_autoload_url'];
		}

		return $this->$property_name = $value;
	}

	public function __unset($property_name) {
		if (isset(static::$writable_properties[$property_name])) {
			return $this->$property_name = null;
		}

		throw new \InvalidArgumentException("Property `{$property_name}` does not exist");
	}

	#region Builders

	/**
	 * @param string|array|null $system_autoload_url
	 *
	 * @return UnitSandbox
	 */
	public static function init($system_autoload_url = null)
	{
		if (!self::$instance instanceof static) {
			self::$instance = new static();

			$system_autoload_url = (array) $system_autoload_url;

			foreach ($system_autoload_url as $route) {
				if ($route === null) {
					continue;
				}

				if (!is_string($route) ) {
					throw new \InvalidArgumentException('$system_autoload_url has to be string or array');
				}

				if (!is_file($route)) {
					throw new \InvalidArgumentException("There is no file: '{$route}'");
				}

				if (substr($route, -4) !== '.php') {
					throw new \InvalidArgumentException("Autoloader has to be .php file");
				}

				self::$instance->sandbox_data['system_autoload_url'][] = $route;
			}
		}

		return self::$instance;
	}

	/**
	 * @param string $serialized_object - serialized instance of class PHPUnitSandbox
	 *
	 * @return UnitSandbox
	 */
	public static function recoveryInstance($serialized_object)
	{
		$callback = unserialize(urldecode($serialized_object));

		self::init();
		self::$instance->sandbox_data = $callback();

		return self::$instance;
	}

	/**
	 *
	 * @param Closure $function
	 *
	 * @return bool|string
	 */
	public static function execute(Closure $function)
	{
		self::init();

		// Wrap the closure
		self::$instance->sandbox_data['execute_this_code'] = $function;

		foreach (self::$instance->mock_class_instance as $class_name => $instance) {
			self::$instance->sandbox_data['mocked_classes'][$class_name] = $instance->getMethods();
		}

		$object = self::$instance->sandbox_data;

		//Clean executing code
		self::$instance->sandbox_data['execute_this_code'] = null;

		$request = 'php ' . static::SANDBOX_FILE_ROUTE . ' ' . static::CLI_ARGV . '=' . urlencode(serialize(new SerializableClosure(function() use ($object) {return $object;})));

		if (self::$debugMode) {
			echo "[BEGIN]\n" .
				"------------------------\n";
			$response = system($request);
			echo "\n" .
				"------------------------\n" .
				"[END]\n";
		} else {
			$response = exec($request);
		}

		$response = json_decode($response,true);

		if (isset($response['throwed'])) {
			$throwed = $response['throwed'];

			throw new $throwed("{$response['message']}\nThrowed at: {$response['trace']}\n");
		}

		return $response;
	}

	#endregion

	#region Object methods

	public function registerMockedData()
	{
		foreach ($this->sandbox_data['mocked_classes'] as $class_name => $methods_list) {
//			___dump(self::buildClassBody($class_name, $methods_list),1,1);
			eval(self::buildClassBody($class_name, $methods_list));
		}

		return $this;
	}

	public function registerAutoloader()
	{
		foreach ($this->sandbox_data['system_autoload_url'] as $autoloader) {
			require_once $autoloader;
		}

		return $this;
	}

	public function run()
	{
		$callback = $this->sandbox_data['execute_this_code'];

		return $callback();
	}

	#endregion

	#region Mock methods

	public static function mockClass($class_name)
	{
		self::init();

		if (empty($class_name) || !is_string($class_name)) {
			throw new \InvalidArgumentException('Invalid $class_name');
		}

		if ($class_name[0] == '\\') {
			$class_name = substr($class_name, 1);
		}

		if (!isset(self::$instance->mock_class_instance[$class_name]) || !self::$instance->mock_class_instance[$class_name] instanceof MockClass) {
			self::$instance->mock_class_instance[$class_name] = new MockClass();
		}

		return self::$instance->mock_class_instance[$class_name];
	}

	#endregion

	#region Helpers

	public static function callMethod($class_name, $method_name)
	{
		if (!is_string($class_name)) {
			throw new \InvalidArgumentException('Invalid $class_name');
		}

		if (!is_string($method_name)) {
			throw new \InvalidArgumentException('Invalid $method_name');
		}

		if ($class_name[0] == '\\') {
			$class_name = substr($class_name, 1);
		}

		$callback = self::$instance->sandbox_data['mocked_classes'][$class_name];

		if (!array_key_exists('return', $callback[$method_name])) {
			throw new \InvalidArgumentException('There was not found methods with name "' . $method_name . '"');
		}

		return $callback[$method_name]['return'];
	}

	private static function buildClassBody($class_name, $methods_list)
	{
		$namespace = explode('\\', $class_name);
		$count = count($namespace) - 1;
		$class_name = $namespace[$count];

		unset($namespace[$count]);

		$namespace = implode('\\', $namespace);

		$methods = '';

		//Build Methods body
		foreach ($methods_list as $method_name => $method_data) {
			$call_type = ($method_data['call_type'] == static::CALL_TYPE_STATIC ? static::CALL_TYPE_STATIC : '');

			$return = '$this';

			if ($call_type === UnitSandbox::CALL_TYPE_STATIC) {
				$return = 'new self()';
			}

			$methods .= "
	public {$call_type} function {$method_name}()
	{
		\$result = UnitSandbox::callMethod('{$namespace}\\{$class_name}', '{$method_name}');
		
		if (\$result === UnitSandbox::SELF_INSTANCE) {
			return {$return};
		} elseif (\$result instanceof \Closure) {
			return \$result();
		}
		
		return \$result;
	}";
		}

		$namespace = !empty($namespace) ? "namespace {$namespace};\n" : '';

		return "{$namespace}
use WebPachage\PHPUnitSandbox\UnitSandbox;

class {$class_name}
{
	{$methods}
}
";
	}

	public static function cleanMockedData()
	{
		if (self::$instance instanceof static) {
			self::$instance->sandbox_data = [
				'execute_this_code' => null,
				'system_autoload_url' => [],
				'mocked_classes' => [],
			];
			self::$instance->mock_class_instance = [];
		}
	}

	public function debugMode($debugMode)
	{
		self::$debugMode = (bool) $debugMode;

		return $this;
	}

	#endregion
}
