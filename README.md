# CakePHP Tools Plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-tools.png?branch=cake3)](https://travis-ci.org/dereuromark/cakephp-tools)
[![Coverage Status](https://coveralls.io/repos/dereuromark/cakephp-tools/badge.png?branch=cake3)](https://coveralls.io/r/dereuromark/cakephp-tools)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tools/license.png)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tools/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x Plugin containing several useful tools that can be used in many projects.

## Version notice

This cake3 branch only works for **CakePHP3.x** - please use the master branch for CakePHP 2.x!
**It is still dev** (not even alpha), please be careful with using it.

### Planned Release Cycle:
Dev (currently), Alpha, Beta, RC, 1.0 stable (incl. tagged release then).

## What is this plugin for?

### Enhancing the core
- Auto-trim on POST (to make - not only notEmpty - validation working properly).
- Disable cache also works for older IE versions.
- With $this->Flash->message() you can have colorful (success, warning, error, ...) flash messages.
  They also can stack up (multiple messages per type) which the core currently doesn't support.
- Provide enum support as "static enums"
- Default settings for Paginator, ... can be set using Configure.
- Provided a less error-prone inArray() method when using Utility class.

### Additional features
- The Passwordable behavior allows easy to use password functionality for frontend and backend.
- Tree helper for working with (complex) trees and their output.
- Ajax Views for better responses (Ajax also comes with an optional component).
- Slugged and Reset behavior
- The Text, Time, Number libs and helpers etc provide extended functionality if desired.
- AuthUser, Timeline, Typography, etc provide additional helper functionality.
- Email as a wrapper for core's Email adding some more usefulness and making debugging/testing easier.

### Providing 2.x shims
This plugin for the Cake 3 version also contains some 2.x shims to ease migration of existing applications from 2.x to 3.x:
- find('first') and find('count')
- Model::$validate, Model::$primaryKey, Model::$displayField and Model relations as properties
- Set/Multibyte class, Session component and a cut down version of JsHelper

## Installation & Docs

- [Documentation](docs/README.md)

### TODOs

* Move more 2.x stuff to 3.x
