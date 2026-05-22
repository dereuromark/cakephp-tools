# Contributing

:+1::tada: First off, thanks for taking the time to contribute! :tada::+1:

The issue tracker is not a support forum. Please keep issues to bug reports and
enhancement proposals. For general CakePHP support, see
<https://cakephp.org/pages/get-involved#userSupport>.

## How to Contribute

1. Fork the repository on GitHub and create a feature branch from `master`.
2. Add tests for any new functionality or bugfix (regression test).
3. Make sure the quality gates pass (see below).
4. Submit a pull request with a clear description of what changed and why.

## Development Setup

```bash
composer install

# Run the plugin migrations
bin/cake migrations migrate -p Tools
```

## Quality Gates

Please run these before submitting (the same checks CI runs):

```bash
composer test        # PHPUnit
composer stan        # PHPStan (run `composer stan-setup` once first)
composer cs-check    # Coding standards (`composer cs-fix` to auto-fix)
```

## Coding Standards

This plugin follows the PSR2R coding standards. Please make sure
`composer cs-check` is green before opening a pull request.

## Updating the Locale POT File

If you change any translatable strings, refresh the plugin's POT file by
running this from your application directory:

```bash
bin/cake i18n extract --plugin Tools --extract-core=no --merge=no --overwrite
```

## Pull Request Guidelines

- Write clear, descriptive commit messages.
- Keep each pull request focused on a single feature or bug fix.
- Update the README/docs when you change user-facing behavior.

## Questions?

Open an issue for discussion if anything is unclear.
