# How to contribute

## Branching strategy
The master branch is the currently active and maintained one and works with the current 2.x stable version.
Older versions might be found in their respective branches (1.3, 2.0, 2.3, ...).
Please provide PRs mainly against master branch then.

## Getting Started

* Make sure you have a [GitHub account](https://github.com/signup/free)
* Fork the repository on GitHub.

## Making Changes

I am looking forward to your contributions. There are several ways to help out:
* Write missing testcases
* Write patches for bugs/features, preferably with testcases included

There are a few guidelines that I need contributors to follow:
* Coding standards (see link below)
* Passing tests (you can enable travis to assert your changes pass) for windows and unix

Protip: Use my [MyCakePHP](https://github.com/dereuromark/cakephp-codesniffer/tree/master/Vendor/PHP/CodeSniffer/Standards/MyCakePHP) sniffs to
assert coding standards are met. You can either use this pre-build repo and the convenience shell command `cake CodeSniffer.CodeSniffer run -p Tools --standard=MyCakePHP` or the manual `phpcs --standard=MyCakePHP /path/to/Tools`.

# Additional Resources

* [My coding standards (extending the CakePHP ones)](http://www.dereuromark.de/coding-standards/)
* [CakePHP coding standards](http://book.cakephp.org/2.0/en/contributing/cakephp-coding-conventions.html)
* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)