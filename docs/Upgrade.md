# Migration from 2.x to 3.x

## TinyAuth
- TinyAuth has been moved to its own [plugin](https://github.com/dereuromark/cakephp-tinyauth).

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

