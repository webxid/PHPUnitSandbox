# PHPUnit Sandbox

This package helps to make a mockups for unit-testing in PHP v5.4 - 7.x

*Current version* 0.1
\
*Source* [https://github.com/webpackage-pro/PHPUnitSandbox](https://github.com/webpackage-pro/PHPUnitSandbox)

## How To Use

A mocked up methods do work only inside sandbox!

**Mock up static method**
```
UnitSandbox::mockClass('DB')
    ->mockStaticMethod('query', UnitSandbox::SELF_INSTANCE); //return self instance
```

**Mock up object method**
```
UnitSandbox::mockClass('DB')
    ->mockStaticMethod('query', UnitSandbox::SELF_INSTANCE)
    ->mockMethod('execute', [1,2,3]);
```

**Run logic inside sandbox**
```
//Make mock up of DB::query()->execute();
UnitSandbox::mockClass('DB')
    ->mockStaticMethod('query', UnitSandbox::SELF_INSTANCE)
    ->mockMethod('execute', [1,2,3]);
    
//Get result of mocked up methods
$result = UnitSandbox::execute(function () {
    return \DB::query()
        ->execute();
});
```

Mocked up classes are working inside other instances too.
```
class TestSandbox
{
    public static function init()
    {
        //There is some code
        
        return DB::query()
            ->execute();
    }
}
```

```
//Get result of TestSandbox::init();
$result = UnitSandbox::execute(function () {
    return \TestSandbox::init();
});

``` 
Both cases `$result` contains array `[1,2,3]`.

*Please, see full example in `example/ExampleUnitTest.php`*

## Install

PHPUnitSandbox does not require any special installation, PHP core extension or etc. 
\
Download and use it.

**Requirements**
- PHP v5.4 or later,
- PHPUnitSandbox has to be includes directly (no autoloaders supports, including Composer),
- PHP config has to support functions `exec()` and `eval()`.

**Installation steps** 

1. Download source code
2. Include `PHPUnitSandbox/autoloader.php` via `include_once` or `require_once` before unit-test
3. Register necessary autoloaders
```
UnitSandbox::init([
        __DIR__ . '/../autoloader_1.php', // it has to be absolute route of autoloader file, and it has to be .php file
        ...
        __DIR__ . '/../autoloader_n.php',
    ])
    ->registerAutoloader();
```

**!!! Important !!!** for sandbox correct work, all autoloaders have to register via `UnitSandbox`, otherwise you get failed.

## Issues

PHPUnitSandbox cannot throw error in case some class and method had mocked up and sandbox logic calls wrong method name. 
\
*Be careful with your code.*

## License

**PHPUnitSandbox** is licensed under the Apache v2.0.
 