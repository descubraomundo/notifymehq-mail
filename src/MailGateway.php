<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Mail;

use NotifyMeHQ\Contracts\GatewayInterface;
use NotifyMeHQ\NotifyMe\Response;
use Html2Text\Html2Text;

class MailGateway implements GatewayInterface
{
    /**
     * The SwiftMailer Mailer.
     *
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * Details of who is sending the email notification.
     *
     * @var array | string
     */
    protected $sender;

    /**
     * Create a new Mail gateway instance.
     *
     * @param \GuzzleHttp\Client $client
     * @param string[]           $config
     *
     * @return void
     */
    public function __construct($config, $mailer, $sender)
    {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->sender = $sender;
    }

    /**
     * Send a notification.
     *
     * @param string $to
     * @param array $message
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    public function notify($to, $message)
    {
        $notification = $this->processEmailProperties($to, $message);

        return $this->send($notification);
    }

    /**
     * Send the notification over the wire.
     *
     * @param array $notification
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function send($notification)
    {
        $success = false;
        $email   = $this->createEmail($notification);

        try {
            if ($this->mailer->send($email, $failures)) {
                $success = true;
                $response = [1];
            } else {
                $response['error'] = sprintf('Failed to send emails for the following recipients: %r', print_r($failures, true));
            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $this->mapResponse($success, $response);
    }

    /**
     * Create the Email Notification.
     * @param  array  $emailProperties The properties that we should create the email with.
     *
     * @return Swift_Message The SwiftMailer Email Object.
     */
    protected function createEmail($emailProperties)
    {
        // Create the Email Message
        $email   = \Swift_Message::newInstance();

        // Configure the sender correctly.
        $this->configureSender($emailProperties, $email);
        // Configure all the recipients correctly.
        $this->addRecipientsToEmail($emailProperties, $email);

        // Set the body of the email correctly.
        if(array_key_exists('body', $emailProperties)) {
            $body = $emailProperties['body'];
            // Check if there is more then one body content type.
            if(is_array($body)){
                if(array_key_exists('html', $body)) {
                    $email->setBody($body['html'], 'text/html');
                }
                if(array_key_exists('plain', $body)){
                    $email->addPart($body['plain'], 'text/plain');
                }
            } else {
                $email->setBody($body);
            }
            // Remove it so we don't process it automatically below.
            unset($emailProperties['body']);
        }

        // After the email properties were defined correctly, automatically call the setter of the SwiftMailer Email object.
        foreach ($emailProperties as $property => $value) {
            // Get setter name.
            $setter = 'set' . ucfirst($property);
            // Check if the setter exists.
            if(method_exists($email, $setter)){
                // CALL IT.
                $email->{$setter}($value);
            }
        }

        return $email;
    }
    /**
     * Add all the recipients for the email correctly.
     *
     * @param array &$emailProperties All the emails properties
     * @param Swift_Message &$email The SwiftMailer Email Object.
     */
    protected function addRecipientsToEmail(&$emailProperties, &$email)
    {
        // Set the recipients correctly
        $recipient = $emailProperties['to'];
        if (is_array($recipient)) {
            $email->setTo($recipient[0],$recipient[1]);
        } else {
            $email->setTo($recipient);
        }
        // Remove it so we don't process it automatically below.
        unset($emailProperties['to']);

        // CC Recipients
        if(array_key_exists('cc',$emailProperties)){
            $ccRecipient = $emailProperties['cc'];
            if (is_array($ccRecipient)) {
                $email->setCc($ccRecipient[0],$ccRecipient[1]);
            } else {
                $email->setCc($ccRecipient);
            }
            // Remove it so we don't process it automatically below.
            unset($emailProperties['cc']);
        }

        // BCC Recipients
        if(array_key_exists('bcc',$emailProperties)){
            $bccRecipient = $emailProperties['bcc'];
            if (is_array($bccRecipient)) {
                $email->setBcc($bccRecipient[0],$bccRecipient[1]);
            } else {
                $email->setBcc($bccRecipient);
            }
            // Remove it so we don't process it automatically below.
            unset($emailProperties['bcc']);
        }
    }

    /**
     * Configure the sender of the email correctly.
     *
     * @param array &$emailProperties All the emails properties
     * @param Swift_Message &$email The SwiftMailer Email Object.
     */
    protected function configureSender(&$emailProperties, &$email)
    {
        // Allow FROM Overwrite
        $sender = array_key_exists('from', $emailProperties) ? $emailProperties['from'] : $this->sender;
        //Check if there is a Name for the sender or it's only email, and configure correctly.
        if(is_array($sender)){
            $email->setFrom($sender[0],$sender[1]);
        } else {
            $email->setFrom($sender);
        }
    }
    /**
     * Process the email properties correctly to be able to correctly configure the SwiftMailer Email object.
     * @param  string|array $to To whom the notification should be sent.
     * @param  array $message All the email properties.
     *
     * @return array The array of all allowed properties for SwiftMailer Email object.
     */
    protected function processEmailProperties($to, $message)
    {
        // If the notification is only a string, create it a email as the notification being the subject of the email
        $original_message = $message;
        if (!is_array($message)) {
            $message = array(
                // Remove HTML tags and trim it to a max length of 75.
                'subject' => substr(strip_tags($original_message),0,75).'...',
                'body' => $original_message,
            );
        }
        //Checks is the message is a HTML message, and generate the plain text version of it.
        $body = $message['body'];
        if (!is_array($body)) {
            $message['body'] = array(
                'html' => $body,
                'plain' => Html2Text::convert($body),
            );
        }


        // Merge and Overwrite configurations.
        $message = array_merge($this->config, $message);

        $recipients          = $this->handleEmailRecipientsProperties($to);
        $body                = $this->handleEmailBodyProperties($message);
        $advancedProperties  = $this->handleAdvancedEmailProperties($message);

        $emailProperties = array_merge($recipients, $body, $advancedProperties);

        return $emailProperties;
    }

    /**
     * Handle all configurations for the email body.
     *
     * @param  array $message The email body properties.
     *
     * @return array          The array with body properties.
     */
    protected function handleEmailBodyProperties($message)
    {
        // Checks if the email has a replyTo.
        if(array_key_exists('replyTo', $message)){
            $emailBodyProperties['replyTo'] = $message['replyTo'];
        }
        // Checks if the email has a subject.
        if(array_key_exists('subject', $message)){
            $emailBodyProperties['subject'] = $message['subject'];
        }
        // Checks if the email has a body.
        if(array_key_exists('body', $message)){
            $emailBodyProperties['body'] = $message['body'];
            // If the content of the Body is HTML but the user didn't specify
            // we mark it as a HTML email
            if(
               !is_array($emailBodyProperties['body']) &&
               $this->isHtml($emailBodyProperties['body'])
            ){
                $emailBodyProperties['contentType'] = 'text/html';
            }
        }

        return $emailBodyProperties;
    }

    /**
     * Handle all configurations for the email recipients.
     *
     * @param  array $recipients The email recipients properties.
     *
     * @return array          The array with recipients properties.
     */
    protected function handleEmailRecipientsProperties($recipients) {
        // Checks if $recipients is an array and has the option to send a CC copy.
        if(is_array($recipients) && array_key_exists('cc', $recipients)){
            $recipientsProperties['cc'] = $recipients['cc'];
            unset($recipients['cc']);
        }

        // Checks if $recipients is an array and has the option to send a CC copy.
        if(is_array($recipients) && array_key_exists('bcc', $recipients)){
            $recipientsProperties['bcc'] = $recipients['bcc'];
            unset($recipients['bcc']);
        }

        $recipientsProperties['to'] = $recipients;

        return $recipientsProperties;
    }

    /**
     * Handle all advanced email message configurations.
     *
     * @param  array $configs The advanced email message configurations.
     *
     * @return array          The array with advanced configurations.
     */
    protected function handleAdvancedEmailProperties($configs)
    {
        $advancedEmailProperties = [];
        if(empty($configs)) {
            return $advancedEmailProperties;
        }
        // Checks if the email has a date.
        if(array_key_exists('date', $configs)){
            $advancedEmailProperties['date'] = $configs['date'];
        }
        // Checks if the email has a contentType.
        if(array_key_exists('contentType', $configs)){
            $advancedEmailProperties['contentType'] = $configs['contentType'];
        }
        // Checks if the email has a returnPath.
        if(array_key_exists('returnPath', $configs)){
            $advancedEmailProperties['returnPath'] = $configs['returnPath'];
        }
        // Checks if the email has a id.
        if(array_key_exists('id', $configs)){
            $advancedEmailProperties['id'] = $configs['id'];
        }
        // Checks if the email has a priority.
        if(array_key_exists('priority', $configs)){
            $advancedEmailProperties['priority'] = $configs['priority'];
        }

        return $advancedEmailProperties;
    }

    /**
     * Map the raw response to our response object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \NotifyMeHQ\Contracts\ResponseInterface
     */
    protected function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'success' => $success,
            'message' => $success ? 'Message sent' : $response['error'],
        ]);
    }

    /**
     * Check if string is has HTML on it on is plain text.
     *
     * @param  string  $string The string we should check.
     *
     * @return boolean         Is a string with HTML or not
     */
    private function isHtml($string)
    {
        if ( $string != strip_tags($string) )
        {
            return true; // Contains HTML
        }
        return false; // Does not contain HTML
    }
}
