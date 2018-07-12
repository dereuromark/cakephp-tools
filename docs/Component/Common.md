# Common component

## Trimming payload data
By default, adding the Common component to your AppController will make sure your POST and query params are trimmed.
This is needed to make - not only notEmpty - validation working properly.

You can skip for certain actions using `'DataPreparation.notrim'` config key per use case.

## Is Post Check
A convenience method can quickly check on a form being posted:
```php
if ($this->Common->isPosted()) {}
```
Saves you the trouble of checking for `post`, `patch`, `put` etc together, and in most cases this is not necessary. It is only important it wasn't a `get` request.

## Secure redirects back
Sometimes you want to post to an edit or delete form and make sure you get redirected back to the correct action including query strings (e.g. for filtering).
Then you can pass `redirect` key as either as part of POST payload or as query string.

```
// In your action
$redirectUrl = $this->Common->getSafeRedirectUrl(['action' => 'default']);
return $this->redirect($redirectUrl);
```

It is important to not use the payload data without sanitation for security reasons (redirect forgery to external websites).

## Default URL params

`CommonComponent::defaultUrlParams()` will give you the default params you might want to combine with your URL 
in order to always generate the right URLs even if inside plugins or prefixes.

## Current URL

`$this->Common->currentUrl()` returns current url (with all missing params automatically added).

## autoRedirect()
A shortcut convenience wrapper for referrer redirecting with fallback:
```php
return $this->Common->autoRedirect($defaultUrl);
```
Set the 2nd param to true to allow redirecting to itself (if that was the referer).

## completeRedirect()
Automatically also adds the query string into the redirect. Useful when you want to keep the filters and pass them around.
```php
return $this->Common->completeRedirect($redirectUrl);
```

 
## getPassedParam()
Convenience method to get passed params:
```php
$param = $this->Common->getPassedParam('x', $default);
```

## isForeignReferer()
Check if a certain referer is a non local one:
```php
// using env referer
$result = $this->Common->isForeignReferer();
// or explicit value
$result = $this->Common->isForeignReferer($urlString);
```

 
 ## Listing actions
 
 If you need all current (direct) actions of a controller, call
 ```php
 $this->Common->listActions()
 ```
