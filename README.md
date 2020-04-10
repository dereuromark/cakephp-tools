# CakePHP Tools Plugin
[![Build Status](https://api.travis-ci.com/dereuromark/cakephp-tools.svg?branch=master)](https://travis-ci.com/dereuromark/cakephp-tools)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-tools/master.svg)](https://codecov.io/gh/dereuromark/cakephp-tools)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-tools/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-tools/license.svg)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-tools/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP plugin containing several useful tools that can be used in many projects.

## Version notice

This master branch only works for **CakePHP 4.0+**. See [version map](https://github.com/dereuromark/cakephp-tools/wiki#cakephp-version-map) for details.

## What is this plugin for?

### Enhancing the core
- Auto-trim on POST (to make - especially notEmpty/notBlank - validation working properly).
- Disable cache also works for older IE versions.
- Provide enum support as "static enums"
- Default settings for Paginator, ... can be set using Configure.
- Provided a less error-prone inArray() method via Utility class and other usefulness.
- TestSuite enhancements
- A few more Database Type classes

### Additional features
- Passwordable behavior allows easy to use password functionality for frontend and backend.
- MultiColumnAuthenticate for log-in with e.g. "email or username".
- Slugged, Reset and other behaviors
- Tree helper for working with (complex) trees and their output.
- Progress and Meter helper for progress bar and meter bar elements (HTML5 and textual).
- Text, Time, Number libs and helpers etc provide extended functionality if desired.
- QrCode, Gravatar and other useful small helpers
- Timeline, Typography, etc provide additional helper functionality.
- Email as a wrapper for core's Email adding some more usefulness and making debugging/testing easier.
- I18n language detection and switching

### Providing 3.x shims
This plugin for CakePHP 4 also contains some 3.x shims to ease migration of existing applications from 3.x to 4.x:
- See [Shim](https://github.com/dereuromark/cakephp-shim) plugin for details on most of the provided shims.

## Installation & Docs

- [Documentation](docs/README.md)

### TODOs

* Move more 3.x stuff to 4.x
