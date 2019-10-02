# Contributing

## Getting Started

* Make sure you have a [GitHub account](https://github.com/signup/free)
* Fork the repository on GitHub.

## Making Changes

I am looking forward to your contributions. There are several ways to help out:
* Write missing testcases
* Write patches for bugs/features, preferably with testcases included

There are a few guidelines that I need contributors to follow:
* Coding standards (`composer cs-check` to check and `composer cs-fix` to fix)
* Passing tests (you can enable travis to assert your changes pass) for Windows and Unix (`php phpunit.phar`)

## i18n 
Check if translations via pot file need to be changed.
Run
```
bin/cake i18n extract -p Tools --extract-core=no --merge=no --overwrite
```
from your app.


# Additional Resources

* [Coding standards guide (extending/overwriting the CakePHP ones)](https://github.com/php-fig-rectified/fig-rectified-standards/)
* [CakePHP coding standards](http://book.cakephp.org/3.0/en/contributing/cakephp-coding-conventions.html)
* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)
