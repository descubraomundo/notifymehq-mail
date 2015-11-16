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

use NotifyMeHQ\Contracts\FactoryInterface;
use NotifyMeHQ\NotifyMe\Arr;
use \Swift_SmtpTransport;
use \Swift_Mailer;

class MailFactory implements FactoryInterface
{
    /**
     * Create a new mail gateway instance.
     *
     * @param string[] $config
     *
     * @return \NotifyMeHQ\Mail\MailGateway
     */
    public function make(array $config)
    {
        Arr::requires($config, [
            'host',
            'port',
            'encryption',
            'username',
            'password',
            'from',
        ]);

        // SwiftMailer Configuration
        $transport = Swift_SmtpTransport::newInstance();
        $transport->setHost($config['host']);
        $transport->setPort($config['port']);
        $transport->setUsername($config['username']);
        $transport->setPassword($config['password']);
        $transport->setEncryption($config['encryption']);
        // Create the Mailer using the created Transport.
        $mailer    = Swift_Mailer::newInstance($transport);

        return new MailGateway($mailer, $config['from']);
    }
}
