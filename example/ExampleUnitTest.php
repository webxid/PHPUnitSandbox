<?php

use PHPUnit\Framework\TestCase;
use WebPachage\PHPUnitSandbox\UnitSandbox;

require_once __DIR__ . '/../autoloader.php';

UnitSandbox::init(__DIR__ . '/classes/TestClass.php')
	->registerAutoloader();

class ExampleUnitTest extends TestCase
{
	public function testUniSandbox()
	{
		//Make mock up of DB::query()->execute();
		UnitSandbox::mockClass('\MyNSpace\DB')
			->mockStaticMethod('query', UnitSandbox::SELF_INSTANCE)
			->mockMethod('execute', [1,2,3]); // Mock up instance method

		//Get result of TestSandbox::init(); - inside this method class DB is calling
		$result = UnitSandbox::execute(function () {
			return \TestSandbox::init();
		});

		UnitSandbox::cleanMockedData();

		//Check result
		$this->assertEquals([1,2,3], $result, 'UnitSandbox does not work');
	}
}
