# PHPUnit Sandbox

This package helps to make a mockups for unit-testing in PHP v5.4 - 8.x

*Source* [https://github.com/webxid/PHPUnitSandbox](https://github.com/webxid/PHPUnitSandbox)

---

## Install

PHPUnitSandbox does not require any special installation.

**Requirements**
- PHP v5.4 or later,
- PHPUnitSandbox has to be includes directly (no autoloaders supports, including Composer),
- PHP config has to support functions `exec()` and `eval()`.

**Installation steps**

1. Download source code
2. Include `PHPUnitSandbox/bootstrap.php` via `include_once` or `require_once` to a unit-test
3. Register necessary autoloaders
```php
UnitSandbox::init([
        __DIR__ . '/../autoloader_1.php', // it has to be absolute route of autoloader file, and it has to be .php file
        ...
        __DIR__ . '/../autoloader_n.php',
    ])
    ->registerAutoloader();
```

**!!! Important !!!** to make sandbox works correctly, the all autoloaders have to be register via `UnitSandbox`, otherwise you get failed. Also, please, check [./bootstrap.php](./bootstrap.php)

---

## How To Use

This lib works as sandbox: 
- First, you setup a mock up of a class and of the class methods.
- Then, you call `UnitSandbox::execute(function() {});` and pass mocked class using inside the `function() {}`.

**Example**
```php
// Setup a mock up of a class `DB` and of the static method `DB::query()`
UnitSandbox::mockClass('DB')
    ->mockStaticMethod('query', UnitSandbox::SELF_INSTANCE); //return self instance

// Then call the mocked method inside sandbox
$result = UnitSandbox::execute(function () {
    return \DB::query();
});
```

## Code examples

### Mock up a static method and an object method
```php
UnitSandbox::mockClass('DB')
    ->mockStaticMethod('query', UnitSandbox::SELF_INSTANCE) // returns self instance
    ->mockMethod('execute', [1,2,3]); // returns array(1,2,3)
```


### Mocked classes are working inside another classes too

```php
// This class should be able by autoloader
class TestClass
{
    public static function init()
    {
        return DB::query() // the usage of the mocked class
            ->execute();
    }
}
```

```php
// Get result of TestClass::init();
$result = UnitSandbox::execute(function () {
    return \TestClass::init();
});

echo json_encode($result); // returns `[1,2,3]` 

``` 

---

### Spy class

_Spy class_ uses to mock up a part of a class. 


1. We need a `TestClass` for example:
```php
class TestClass
{
    private static $my_property = 'Hello world!';

    public static function getProperty()
    {
        return static::$my_property;
    }
}
```

2. Let's rewrite private property of class TestClass;

```php
UnitSandbox::spyClass('\TestClass')
    ->defineStaticProperty('my_property', 'value');

$result_private_property = UnitSandbox::execute(function () {
    return \Spy\TestClass::getProperty();
});

echo $result_private_property; // it'll prints `value` instead `Hello world!`
```

\
*Please, see all examples in [./tests/ExampleUnitTest.php](./tests/ExampleUnitTest.php) *

---

## Issues

To see errors, occurred inside sandbox, needs to set up debug mode:
 ```php
 UnitSandbox::init()
    ->debugMode(true, false);
 ```

---

## License

**PHPUnitSandbox** is licensed under the Apache v2.0.

---
 
 ## Version log
 v0.3
 - Make composer lib
 - Minor fixes 

 v0.2
 - Mocked class properties defining
 - Pass parameters to mocked methods
 - Spy class logic
 - Minor fixes
 
 v0.1
 - Methods mock up features
 