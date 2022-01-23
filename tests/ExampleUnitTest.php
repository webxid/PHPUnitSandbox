<?php

namespace WebXID\PHPUnitSandbox\Tests;

use PHPUnit\Framework\TestCase;
use WebXID\PHPUnitSandbox\UnitSandbox;

UnitSandbox::init(__DIR__ . '/classes/TestClass.php')
	->registerAutoloader()
	->debugMode(true, false);

class ExampleUnitTest extends TestCase
{
	/**
	 * Test Method mocking up
	 */
	public function testMockClass()
	{
		//Make mock up of DB::query()->execute()->$method_name();
		UnitSandbox::mockClass('\MyNSpace\DB')
			->mockStaticMethod('query', function($self_instance, $args_list) {
//				print_r($args_list); //there is full list of parameters passed to method

				return $self_instance;
			})
			->mockMethod('execute', UnitSandbox::SELF_INSTANCE) // Mock up instance method
			->mockMethod('return_array', [1,2,3])
			->mockMethod('return_int', 1)
			->mockMethod('return_float', 2.13)
			->mockMethod('return_true', true)
			->mockMethod('return_false', false)
			->mockMethod('return_null', null)
			->mockMethod('return_string', 'value');

		//Get result of TestClass::init(); - inside this method class DB is calling
		$result_array = UnitSandbox::execute(function () {
			return \TestClass::init()
				->return_array();
		});
		$this->assertEquals([1,2,3], $result_array, 'Array does not work');

		$result_int = UnitSandbox::execute(function () {
			return \TestClass::init()
				->return_int();
		});
		$this->assertTrue(is_int($result_int), 'Integer does not work');
		$this->assertEquals(1, $result_int, 'Integer does not work');

		$result_float = UnitSandbox::execute(function () {
			return \TestClass::init()
				->return_float();
		});
		$this->assertTrue(is_float($result_float), 'Float does not work');
		$this->assertEquals(2.13, $result_float, 'Float does not work');

		$result_true = UnitSandbox::execute(function () {
			return \TestClass::init()
				->return_true();
		});
		$this->assertTrue(is_bool($result_true), 'True does not work');
		$this->assertTrue($result_true, 'True does not work');

		$result_false = UnitSandbox::execute(function () {
			return \TestClass::init()
				->return_false();
		});
		$this->assertTrue(is_bool($result_false), 'False does not work');
		$this->assertFalse($result_false, 'False does not work');

		$result_null = UnitSandbox::execute(function () {
			return [\TestClass::init()
				->return_null()];
		});
		$this->assertTrue(is_array($result_null), 'Null does not work');
		$this->assertArrayHasKey(0, $result_null, 'Null does not work');
		$this->assertNull($result_null[0], 'Null does not work');

		$result_string = UnitSandbox::execute(function () {
			return \TestClass::init()
				->return_string();
		});
		$this->assertTrue(is_string($result_string), 'String does not work');
		$this->assertEquals('value', $result_string, 'String does not work');

		UnitSandbox::cleanMockedData();
	}

	/**
	 * Test object property mocking up
	 */
	public function testdefineProperty()
	{
		//Make mock up of property \DB::name;
		UnitSandbox::mockClass('\DB')
			->defineProperty('name', 'value');

		$result = UnitSandbox::execute(function () {
			return (new \DB())->name;
		});

		UnitSandbox::cleanMockedData();

		//Check result
		$this->assertEquals('value', $result);
	}

	/**
	 * Test object constant mocking up
	 */
	public function testdefineConst()
	{
		//Make mock up of DB::MY_CONST;
		UnitSandbox::mockClass('\DB')
			->defineConst('MY_CONST', 'value');

		$result = UnitSandbox::execute(function () {
			return \DB::MY_CONST;
		});

		UnitSandbox::cleanMockedData();

		//Check result
		$this->assertEquals('value', $result);
	}

	/**
	 * Test static property mocking up
	 */
	public function testdefineStaticProperty()
	{
		//Make mock up of DB::$name;
		UnitSandbox::mockClass('\DB')
			->defineStaticProperty('name', 'value');

		$result = UnitSandbox::execute(function () {
			return \DB::$name;
		});

		UnitSandbox::cleanMockedData();

		//Check result
		$this->assertEquals('value', $result, 'UnitSandbox does not work');
	}

	/**
	 * Test Spy Class mocking up
	 */
	public function testSpyClass()
	{
		//Rewrite private property for class TestClass;
		UnitSandbox::spyClass('\TestClass')
			->defineStaticProperty('my_property', 'value');

		//Get Concat string "Case: " . "value"
		$result_private_property = UnitSandbox::execute(function () {
			return \Spy\TestClass::getProperty();
		});

		//Rewrite private static method for class TestClass;
		UnitSandbox::spyClass('\TestClass')
			->mockStaticMethod('getString', 'Message: ');

		//Get Concat string "Case: " . "value"
		$result_private_method = UnitSandbox::execute(function () {
			return \Spy\TestClass::getProperty();
		});

		UnitSandbox::cleanMockedData();

		//Check result
		$this->assertEquals('Case: Hello world!', \TestClass::getProperty());
		$this->assertEquals('Case: value', $result_private_property);
		$this->assertEquals('Message: value', $result_private_method);
	}
}
