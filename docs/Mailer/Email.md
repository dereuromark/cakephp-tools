# Email
Built on top of core Email class.

An enhanced class to
- Catches the exception and logs it away. This way most user land code can be kept simple as the try catch block is not needed. It will simply return boolean/success.
- Allow wrapLength() / priority() adjustment.
- Auto-set "from" as admin email (no need to do that in the code unless needs overwriting).
- Enable easier attachment adding (and also from blob).
- Enable embedded images in HTML mails.
- Can work with foreign attachments/images (different domains and alike).
- Auto mimetype detection for attachments (inline or not).
- Basic validation supported.
- Quick way to send system emails/reports.
- Extensive logging and error tracing as well as debugging using.
- Don't send emails without Configure::write('Config.live'), but log them away verbosely. For testing.
- Security measure: Don't send emails to actual addresses in debug mode, they will be sent to Configure::read('Config.adminEmail') instead. Same for cc/bcc.


## Configs
First, replace the use statement in the bootstrap:
```php
//use Cake\Mailer\Email;
use Tools\Mailer\Email;
```
The other two lines can stay as they are:
```php
Email::configTransport(Configure::consume('EmailTransport'));
Email::config(Configure::consume('Email'));
```
They will read from Configure what you defined there, e.g for sending SMTP mails.
```
'Email' => array(
    'default' => array(
        'from' => 'your@email.com'
    )
),
'EmailTransport' => array(
    'default' => array(
        'className' => 'Smtp',
        'host' => 'smtp.hostname.com',
        'username' => 'your@email.com',
        'password' => 'yourpwd',
        'tls' => true,
        'port' => 587
    )
)
```

That's it.


## Usage
Don't forget the use statement in any class you use it.
Then instantiate it as
```php
$email = new Email();

// Add the rest of the information needed
$email->to($email, $name);
$email->subject($subject);

$email->replyTo($adminEmail, $adminName); // Optional reply to header
$email->template('sometemplate'); // Optional
$email->layout('somelayout'); // Optional

$email->viewVars(compact('message', 'other'));

// Send it
if ($email->send()) {
    // Success
} else {
    // Error
    // You can use $email->getError() and display it in debug mode or log it away
}
```
`from` is already set with your admin email by default, if you configured `Config.adminEmail`.

### Embedded Attachments
This will automatically set the contentId and mimeType of the file. This is necessary for embedded attachments.
```php
$email->addEmbeddedAttachment('/some/file.ext', 'Some custom name');
```

### Blob Attachments - as real attachment
This will automatically set the mimeType of the file (using the filename you provide).
```php
$email->addBlobAttachment($somePngFileBlobContent, 'my_filename.png');
```

### Embedded Blob Attachments
This will also automatically set the mimeType of the file as above. But it will set the contentId, as well. This is necessary for embedded attachments.
```php
$email->addEmbeddedBlobAttachment($somePngFileBlobContent, 'my_filename.png');
```


## Advanced config and usage
You can use `'Config.xMailer'` config to set a custom xMailer header.
Priority and line length can also be adjusted if needed.

By default it switches to "Debug" transport in debug mode. If you don't want that set Configure value `Config.live` to true.
