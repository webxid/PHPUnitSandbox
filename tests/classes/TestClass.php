<?php

class TestClass
{
	private static $my_property = 'Hello world!';

	public static function getProperty()
	{
		return static::getString() . static::$my_property;
	}

	private static function getString()
	{
		return 'Case: ';
	}

	public static function init()
	{
		return \MyNSpace\DB::query()
			->execute();
	}
}
