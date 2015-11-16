[![Build Status](https://travis-ci.org/descubraomundo/notifymehq-mail.svg)](https://travis-ci.org/descubraomundo/notifymehq-mail)

# NotifyMeHQ Mail
An **UNOFFICIAL** Mail gateway for NotifyMeHQ as they "*intentionally removed mail support*" for NotifyMeHQ.

If you want to keep the default contract of NotifyMeHQ `public function notify($to, $message);` with both parameters as a string, the expected behavior is to:
  1. Set the subject of the email, as the $message content, but remove all HTML tags and limit to the size of 75 characters.
  2. Set the body of the email, as the $message content it self with the full content and all HTML tags.

Otherwise you can send the `$message` variable as an array with the desired configurations below.

## Configuration
Here is the list of all available configurations that you can provide for you notifierFactory and for your notification.
### Notifier
| Configuration | Required | Description |
| ------------- | ------------- | ----------- |
| host |**true**| Specify your SMTP Server Host. |
| port |**true**| Specify your SMTP Server Port, check with your host. Eg: *25* or *465* or *2525*. |
| encryption |**true**| Specify your SMTP Server Encryption, check with your host. Eg: *tls* or *ssl*. |
| username |**true**| Specify your SMTP Server Username. |
| password |**true**| Specify your SMTP Server Password. |
| subject |*optional*| Specifies the subject line that is displayed in the recipients' mail client. |
| cc |*optional*| Specifies the addresses of recipients who will be copied in on the message. |
| bcc |*optional*| Specifies the addresses of recipients who the message will be blind-copied to. Other recipients will not be aware of these copies. |
| replyTo |*optional*| Specifies the address where replies are sent to. |
| from |*optional*| Specifies the sender address if you want to overwrite the value set on the configuration properties. |
| contentType |*optional*| Specifies the format of the message (usually text/plain or text/html). |
| date |*optional*| Specifies the unix time stamp date at which the message was sent. |
| returnPath |*optional*| Specifies where bounces should go (Swift Mailer reads this for other uses). |
| priority |*optional*| Specifies the email priority. Setting the priority will not change the way your email is sent It is purely an indicative setting for the recipient. |

### Notification
If you provide any of the configuration bellow, they will overwrite the default configuration provided for the notifierFactory.

| Configuration | Required | Description |
| ------------- | ------------- | ----------- |
| subject |**true**| Specifies the subject line that is displayed in the recipients' mail client. |
| body |**true**| Specifies the body of the email that the recipient will receive. |
| cc |*optional*| Specifies the addresses of recipients who will be copied in on the message. |
| bcc |*optional*| Specifies the addresses of recipients who the message will be blind-copied to. Other recipients will not be aware of these copies. |
| replyTo |*optional*| Specifies the address where replies are sent to. |
| from |*optional*| Specifies the sender address if you want to overwrite the value set on the configuration properties. |
| contentType |*optional*| Specifies the format of the message (usually text/plain or text/html). |
| id |*optional*| Identifies this message with a unique ID, usually containing the domain name and time generated. |
| date |*optional*| Specifies the unix time stamp date at which the message was sent. |
| returnPath |*optional*| Specifies where bounces should go (Swift Mailer reads this for other uses). |
| priority |*optional*| Specifies the email priority. Setting the priority will not change the way your email is sent It is purely an indicative setting for the recipient. |

### Advanced
#### body
For the body value you can provide both versions(HTML and plain text):
```php
    'body'    => [
        'html'  => 'My <em>amazing</em> body',
        'plain' => 'My amazing body'
    ],
```
Or you can provide only one version, and we will detect automatically if is HTML or plain text and configure correctly:
```
    'body'    => 'My <em>amazing</em> body'  // It will be sent as HTML
```
OR
```
    'body'    => 'My amazing body'           // It will be sent as plain text
```

#### from, to, cc, bcc, replyTo
For all email recipients / sender configuration you can provide them in two variants:

*Email and Name* (As an array)
```
    'from|to|cc|bcc|replyTo' => ['email@example.com', 'Example Name'],
```
*Email Only* (As a string)
```
    'from|to|cc|bcc|replyTo' => 'email@example.com',
```

#### priority
This configuration takes an integer value between 1 and 5:
* Highest (1)
* High (2)
* Normal (3)
* Low (4)
* Lowest (5)

## Example

```php
<?php
    // Create a factory for notifications.
    $notifierFactory = new NotifyMeHQ\NotifyMe\NotifyMeFactory();

    // Create the new notification for mail.
    $mailNotifier = $notifierFactory->make([
      // Specify that we will use mail.
      'driver' => 'mail',
      // Specify your SMTP Server Host.
      'host' => '',
      // Specify your SMTP Server Port, check with your host. Eg: 25 or 465 or 2525.
      'port' => 25,
      // Specify your SMTP Server Username.
      'username' => '',
      // Specify your SMTP Server Password.
      'password' => '',
      // Specify your Sender details. It can be a simple email, or a email with a name.
      'from' => ['from@example.com','Example Sender'], // Email & Name
    ]);

    /**
     * RECIPIENTS:
     */
    $recipient = ['recipient@example.com', 'Recipient Name'];

    /**
     * EMAIL
     */
    $email = [
        // Specifies the subject line that is displayed in the recipients' mail client
        'subject' => 'Test',
        // Specifies the body of the email that the recipient will receive.
        'body'    => [
            'html'  => 'My <em>amazing</em> body',
            'plain' => 'My amazing body'
        ],
        // Specifies the addresses of recipients who will be copied in on the message
        'cc' => ['cc@email.com', 'CC Name'],
        // Specifies the addresses of recipients who the message will be blind-copied to. Other recipients will not be aware of these copies.
        'bcc' => ['bcc@email.com', 'BCC Name'],
        // Specifies the address where replies are sent to
        'replyTo' => ['replyto@email.com', 'Reply To Name'],
        // Specifies the sender address if you want to overwrite the value set on the configuration properties.
        'from' => ['otherFrom@email.com', 'Other Sender Name'],

        /**
         * ADVANCED OPTIONS
         */
        // Specifies the format of the message (usually text/plain or text/html)
        'contentType' => 'text/html',
        // Specifies where bounces should go (Swift Mailer reads this for other uses)
        'returnPath' => 'bounces@email.com',
        // Specifies the email priority.
        'priority' => '2', // Indicates "High" priority.
    ];

    /* @var \NotifyMeHQ\Contracts\ResponseInterface $response */
    $response =  $mailNotifier->notify($recipient, $email);

    echo $response->isSent() ? 'Message sent' : 'Message going nowhere';
```
## Todo
- [ ] Add tests

## License

NotifyMe is licensed under [The MIT License (MIT)](LICENSE).
