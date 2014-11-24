# Migration from 2.x to 3.x

## TinyAuth
- TinyAuth has been moved to its own [plugin](https://github.com/dereuromark/cakephp-tinyauth).

## Auth
- As session is not static anymore Auth class has been refactored into component and helper (incl. trait) and can be used as AuthUser.

## Geo
- Behaviors Geocodable and Helper GoogleMapsV3 are now moved to a separate [Geo plugin](https://github.com/dereuromark/cakephp-geo).

## Utility
- *Lib are now just * classes
- Utility::getMimeType() is now Mime::detectMimeType()
- $this->Time->isLeapYear() now doesnt take an argument anymore

## Controller
- Flash message functionality has been extracted into Flash component and Flash helper.
- $this->Common->flashMessage() is now $this->Flash->message().
- $this->Common->transientFlashMessage() is now $this->Flash->transientMessage().

## Behavior
- `run`/`before` config options for callback decisions have been unified to `on` and complete callback/event name, e.g. `'on' => 'beforeValidate'`.
- model names are now table names, and plural.
- Slugged option "slugField" is now "field"