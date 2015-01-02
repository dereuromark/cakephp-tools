# Contributing
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/dereuromark/cakephp-tools?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

## Getting Started

* Make sure you have a [GitHub account](https://github.com/signup/free)
* Fork the repository on GitHub.

## Making Changes

I am looking forward to your contributions. There are several ways to help out:
* Write missing testcases
* Write patches for bugs/features, preferably with testcases included

There are a few guidelines that I need contributors to follow:
* Coding standards (see link below)
* Passing tests (you can enable travis to assert your changes pass) for Windows and Unix

Protip: Use my [MyCakePHP](https://github.com/dereuromark/cakephp-codesniffer/tree/master/Vendor/PHP/CodeSniffer/Standards/MyCakePHP) sniffs to
assert coding standards are met. You can either use this pre-build repo and the convenience shell command `cake CodeSniffer.CodeSniffer run -p Tools --standard=MyCakePHP` or the manual `phpcs --standard=MyCakePHP /path/to/Tools`.

# Additional Resources

* [Coding standards guide (extending/overwriting the CakePHP ones)](https://github.com/php-fig-rectified/fig-rectified-standards/)
* [CakePHP coding standards](http://book.cakephp.org/3.0/en/contributing/cakephp-coding-conventions.html)
* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)
