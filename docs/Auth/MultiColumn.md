# MultiColumnAuthenticate

By default the FormAuthenticate class only allows a single field to be used.
But often times you want to provide a login input field as combined one, so the user can log in with either email or username.

For this make sure your login form contains:
```php
echo $this->Form->control('login');
echo $this->Form->control('password', ['autocomplete' => 'off']);
```

Then set up the Auth component:
```php
    //in $components
    public $components = [
        'Auth' => [
            'authenticate' => [
                'Tools.MultiColumn' => [
                    'fields' => [
                        'username' => 'login',
                        'password' => 'password'
                    ],
                    'columns' => ['username', 'email'],
                ]
            ]
        ]
    ];

    // Or in beforeFilter()
    $this->Auth->config('authenticate', [
        'Tools.MultiColumn' => [
            'fields' => [
                'username' => 'login',
                'password' => 'password'
            ],
            'columns' => ['username', 'email'],
        ]
    ]);
```

Of course you can still combine it with custom finders to add a scope or contain relations just as the core Form one.
