<?php

namespace WebXID\PHPUnitSandbox;

use Closure;
use WebXID\PHPUnitSandbox\Helper\BuildClassBody;
use WebXID\PHPUnitSandbox\Helper\MockClass;
use Opis\Closure\SerializableClosure;

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

	const VAR_TYPE_CONST = 'const';
	const VAR_TYPE_STATIC_PROPERTY = 'static property';
	const VAR_TYPE_PROPERTY = 'property';

	/**
	 * @var static
	 */
	protected static $instance;
	protected static $is_sandbox = false;
	protected static $readable_properties = [];
	protected static $writable_properties = [
		'system_autoload_url' => true,
	];
	private static $print_result = false;
    private static $print_class = false;

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
	 * @param string|array|null $include_files
	 *
	 * @return UnitSandbox
	 */
	public static function init($include_files = null)
	{
		if (!self::$instance instanceof static) {
			self::$instance = new static();

			$include_files = (array) $include_files;

			foreach ($include_files as $route) {
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
		self::$is_sandbox = true;

		return self::$instance;
	}

	/**
	 *
	 * @param Closure $function
	 *
	 * @return mixed
	 */
	public static function execute(Closure $function)
	{
		self::init();

		// Wrap the closure
		self::$instance->sandbox_data['execute_this_code'] = $function;

		foreach (self::$instance->mock_class_instance as $class_name => $instance) {
			self::$instance->sandbox_data['mocked_classes'][$class_name]['methods'] = $instance->getMethods();
			self::$instance->sandbox_data['mocked_classes'][$class_name]['vars'] = $instance->getVars();
			self::$instance->sandbox_data['mocked_classes'][$class_name]['is_spy'] = $instance->isSpy();
			self::$instance->sandbox_data['mocked_classes'][$class_name]['spy_namespace'] = $instance->getSpyNamespace();
		}

		$object = self::$instance->sandbox_data;

		//Clean executing code
		self::$instance->sandbox_data['execute_this_code'] = null;

		$request = 'php ' . static::SANDBOX_FILE_ROUTE . ' ' . static::CLI_ARGV . '=' . urlencode(serialize(new SerializableClosure(function() use ($object) {return $object;})));

		if (self::$print_result) {
			echo "[BEGIN]\n" .
				"------------------------\n";
			$response = system($request); // Executes and print a result
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
		$this->registerSandboxData(false);

		return $this;
	}

	public function registerSpyData()
	{
		$this->registerSandboxData(true);

		return $this;
	}

	public function registerAutoloader()
	{
		foreach ($this->sandbox_data['system_autoload_url'] as $autoloader) {
			if (!is_file($autoloader)) {
				throw new \InvalidArgumentException('Autoloader: file does not exist at "' . $autoloader . '"');
			}

			require_once $autoloader;
		}

		return $this;
	}

	private function registerSandboxData($is_spy)
	{
		foreach ($this->sandbox_data['mocked_classes'] as $class_name => $class_data) {
			if ($is_spy == $class_data['is_spy']) {
				$code = self::buildClassBody($class_name, $class_data);

				eval($code);
			}
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
	/**
	 * @param string $class_name
	 *
	 * @return MockClass
	 */
	public static function mockClass($class_name)
	{
		if (empty($class_name) || !is_string($class_name)) {
			throw new \InvalidArgumentException('Invalid $class_name');
		}

		if ($class_name[0] == '\\') {
			$class_name = substr($class_name, 1);
		}

		self::init();

		if (!isset(self::$instance->mock_class_instance[$class_name]) || !self::$instance->mock_class_instance[$class_name] instanceof MockClass) {
			self::$instance->mock_class_instance[$class_name] = new MockClass();
		}

		self::$instance->mock_class_instance[$class_name]->setSpyStatus(false);

		return self::$instance->mock_class_instance[$class_name];
	}

	/**
	 * @param string $class_name
	 * @param string $spy_namespace
	 *
	 * @return MockClass
	 */
	public static function spyClass($class_name, $spy_namespace = 'Spy')
	{
		if (empty($class_name) || !is_string($class_name)) {
			throw new \InvalidArgumentException('Invalid $class_name');
		}

		if (empty($spy_namespace) || !is_string($spy_namespace)) {
			throw new \InvalidArgumentException('Invalid $sandbox_namespace');
		}

		return self::$instance->mockClass($class_name)
			->setSpyStatus(true)
			->setSpyNamespace($spy_namespace);
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

		$callback = self::$instance->sandbox_data['mocked_classes'][$class_name]['methods'];

		if (!array_key_exists('return', $callback[$method_name])) {
			throw new \InvalidArgumentException('There was not found methods with name "' . $method_name . '"');
		}

		return $callback[$method_name]['return'];
	}

	private static function buildClassBody($class_name, $class_data)
	{
		$buildClass = BuildClassBody::init($class_name);

		$buildClass->methods = $class_data['methods'];
		$buildClass->spy_namespace = $class_data['spy_namespace'];
		$buildClass->consts = $class_data['vars']['consts'];
		$buildClass->static_properties = $class_data['vars']['static_properties'];
		$buildClass->properties = $class_data['vars']['properties'];
		$buildClass->is_spy = $class_data['is_spy'];

		$class = $buildClass->execute();

		if (self::$print_result) {
			if (self::$print_class) echo $class . "\n";

			echo "Result: \n";
		}

		return $class;
	}

	public static function cleanMockedData()
	{
		if (self::$instance instanceof static) {
			self::$instance->sandbox_data['execute_this_code'] = null;
			self::$instance->sandbox_data['mocked_classes'] = [];
			self::$instance->mock_class_instance = [];
		}
	}

	public function debugMode($print_result, $print_class)
	{
		self::$print_result = (bool) $print_result;
		self::$print_class = (bool) $print_class;

		return $this;
	}

	#endregion
}
