# RefererRedirect component

## Why this component
(Referer) Redirecting is a often wrongly/badly implemented pattern.
Especially the session is here usually the worst possible usability approach.
As soon as you have two tabs open they will basically kill each other, creating very weird experiences for the user.

This component uses a referer key in query string to redirect back to given referer.
The neat thing here is that it doesn't require changes to existing actions. This can just be
added on top, for one or all controllers.

## Alternatives
You can also pass along all query strings always, but then you need to make sure all URLs in controllers and templates are adjusted here.

## Setting it up for a controller
Let's set it up. Inside your controller:
```php
    /**
     * @return void
     */
    public function initialize() {
        parent::initialize();
        $this->loadComponent('RefererRedirect', [
            'actions' => ['edit'],
        ]);
    }
```
We whitelisted the edit action to be auto-referer redirectable.

## Adjust your links to this action

From your paginated and filtered index page you can now point to the edit page like this:

```php
<?php echo $this->Html->link(
    $this->Format->icon('edit'),
    ['controller' => 'Versions', 'action' => 'edit', $version->id, '?' => ['ref' => $this->getRequest()->getRequestTarget()]],
    ['escape' => false]
); ?>
```

After successful save it will then redirect to exactly the current URL including all filter query strings, page, sort order etc.


## When not to use
Make sure you are not using such approaches when linking to an action that removes an entity from the `view`/`edit` action of that entity.
So do not use it for deleting actions while you are on the `view` action of this entity, for example.
Or in that case make sure you pass the referer of the "target" action, e.g. `index` etc. This way the redirect will then not run into an error or redirect loop.

## Security
It is advised to whitelist the actions for valid referer redirect.

Also make sure the referer is always a local one (not pointing to external URLs).
The component here itself already checks for this.
