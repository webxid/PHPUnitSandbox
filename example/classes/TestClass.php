<?php

class TestSandbox
{
	public static function init()
	{
		return \MyNSpace\DB::query()
			->execute();
	}
}
