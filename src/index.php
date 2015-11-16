<?php

require __DIR__ . '/../vendor/autoload.php';

// Create a factory for notifications
$notifierFactory = new NotifyMeHQ\NotifyMe\NotifyMeFactory();

// Create the new notification for slack
$slackNotifier = $notifierFactory->make(array(
  "driver" => "mail",
  "host" => "mailtrap.io",
  "port" => 2525,
  "encryption" => 'ssl',
  "from" => array("from@example.com","Example Sender"),
  "username" => "501089d342d401eec",
  "password" => "d577ae71316fea",
));

/* @var \NotifyMeHQ\Contracts\ResponseInterface $response */
$email = [
    'replyTo' => 'replyto@email.com',
    'from' => 'otherFrom@email.com',
    'id' => '111111111111111111111111111111111111@email.com',
    'date' => '660600626',
    'bcc' => 'bcc@email.com',
    'cc' => 'cc@email.com',
    'returnPath' => 'bounces@email.com',
    'subject' => 'Test',
    'contentType' => 'text/html',
    'body'    => [
        'html' => '<h1>Corpo do email HTML</h1>',
        'plain' => 'Corpot do email plain'
    ]
];

$email = [
    'body'    => [
        'html' => '<h1>Corpo do email HTML</h1>',
        'plain' => 'Corpot do email plain'
    ],
    'subject' =>"dsads"
];

$response =  $slackNotifier->notify(['passarelli.gabriel@gmail.com','Gabriel Passarelli'], $email);
echo $response->isSent() ? 'Message sent' : 'Message going nowhere';
var_dump($response->message());
