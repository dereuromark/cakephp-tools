# Tools

## A cake2.x plugin

This plugin contains several useful tools that can be used in many projects.
Please fork and help to improve (bugfixing, test cases, ...)

Please note: New functionality has been tested against cake2.3 only. Please upgrade if possible.

CODING STANDARDS
- http://www.dereuromark.de/coding-standards/

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

* Put the files in `APP/Plugin/Tools`
* Make sure you have `CakePlugin::load('Tools')` or `CakePlugin::loadAll()` in your bootstrap

Tip: You can also use packagist now @ https://packagist.org/packages/dereuromark/tools-cakephp

That's it. It should be up and running.

## The basics

Include the Tools bootstrap file in your `APP/Config/bootstrap.php` with

    App::import('Lib', 'Tools.Bootstrap/MyBootstrap');

You cannot use App::uses because this file does not contain a class and needs to be included right away (not lazy loaded).


MyModel can be extended to use more powerful validation and other improvements:

    App::uses('MyModel', 'Tools.Model');

    class AppModel extends MyModel {
    }

MyController can be extended for DRY improvements and to fix some common bugs:

    App::uses('MyController', 'Tools.Controller');

    class MyController extends MyController {
    }

MyHelper can be extended and used this way:

    App::uses('MyHelper', 'Tools.View/Helper');

    class AppHelper extends MyHelper {
    }

The test suite improvements can be used via:

    App::uses('MyCakeTestCase', 'Tools.TestSuite');

    class SomeClassTest extends MyCakeTestCase {
    }

To run any of the console commands (replace [ShellName] and [command]!):

    cake Tools.[ShellName] [command]

The models, behaviors, helpers, libs and other classes are used the same way prefixing them with the plugin name:

    App::uses('GooglLib', 'Tools.Lib');
    App::uses('TimeLib', 'Tools.Utility');
    App::uses('GeocoderBehavior', 'Tools.Model/Behavior');
    ...

Tip: For how to use them, try to find some information in the test cases.
Usage for some larger modules: https://github.com/dereuromark/tools/blob/master/USAGE


## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
Some classes are still from 1.2 (and are merely upgraded to 2.x) and might still need some serious refactoring.
If you are able to help on that one, that would be awesome.

### Branching strategy
The master branch is the currently active and maintained one and works with the current 2.x stable version.
Older versions might be found in their respective branches (1.3, 2.0, 2.3, ...).
Please provide PRs mainly against master branch then.

### Recent changes (possibly BC breaking)

* 2013-02 Removed PasswordChangeBehavior in favor of its new name Passwordable

