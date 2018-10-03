# PHPUnit Sandbox

This package helps to make a mockups for unit-testing in PHP v5.4 - 7.x

*Current version* 0.2
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

Mocked classes are working inside other instances too.
```
class TestClass
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
//Get result of TestClass::init();
$result = UnitSandbox::execute(function () {
    return \TestClass::init();
});

``` 
Both cases `$result` contains array `[1,2,3]`.

---

**Spy class**
\
Let's make Spy for `TestClass`
```
class TestClass
{
    private static $my_property = 'Hello world!';

    public static function getProperty()
    {
        //There is some code

        return static::$my_property;
    }
}
```

Rewrite private property of class TestClass;

```
UnitSandbox::spyClass('\TestClass')
    ->defineStaticProperty('my_property', 'value');

$result_private_property = UnitSandbox::execute(function () {
    return \Spy\TestClass::getProperty();
});
```
Variable `$result_private_property` will contains string `value` 

\
*Please, see all examples in `example/ExampleUnitTest.php`*

---

## Issues

To see errors, occurred inside sendbox, needs to switch on debug mode:
 ```
 UnitSandbox::init()
    ->debugMode(true);
 ```

---

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

---

## License

**PHPUnitSandbox** is licensed under the Apache v2.0.

---
 
 ## Version log
 
 v0.2
 - Mocked class properties defining
 - Pass parameters to mocked methods
 - Spy class logic
 - Minor fixes
 
 v0.1
 - Methods mock up features
 