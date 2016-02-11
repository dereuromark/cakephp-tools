# CakePHP Tools Plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-tools.svg?branch=master)](https://travis-ci.org/dereuromark/cakephp-tools)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-tools/master.svg)](https://codecov.io/github/dereuromark/cakephp-tools?branch=master)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tools/license.svg)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tools/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x plugin containing several useful tools that can be used in many projects.

## Version notice

This master branch only works for **CakePHP3.x** - please use the 2.x branch for CakePHP 2.x!

## What is this plugin for?

### Enhancing the core
- Auto-trim on POST (to make - not only notEmpty - validation working properly).
- Disable cache also works for older IE versions.
- Provide enum support as "static enums"
- Default settings for Paginator, ... can be set using Configure.
- Provided a less error-prone inArray() method via Utility class and other usefulness.
- TetSuite enhancements
- A few more Database Type classes
 
### Additional features
- Passwordable behavior allows easy to use password functionality for frontend and backend.
- Slugged, Reset and other behaviors
- Tree helper for working with (complex) trees and their output.
- Text, Time, Number libs and helpers etc provide extended functionality if desired.
- AuthUser, Timeline, Typography, etc provide additional helper functionality.
- Email as a wrapper for core's Email adding some more usefulness and making debugging/testing easier.

### Providing 2.x shims
This plugin for CakePHP 3 also contains some 2.x shims to ease migration of existing applications from 2.x to 3.x:
- See Shim plugin for details on most of the provided shims.
- Cut down version of JsHelper and a few more things.

## Installation & Docs

- [Documentation](docs/README.md)

### TODOs

* Move more 2.x stuff to 3.x
