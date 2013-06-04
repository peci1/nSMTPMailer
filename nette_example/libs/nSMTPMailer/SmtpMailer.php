<?php

//if not using Nette robotloader, point to the SMTPClient.php file
//require_once(dirname(__FILE__) . '/nSMTPMailer/SMTPClient.php')

namespace Nette\Mail\SMTP;

/**
 * This is the bridge between SmtpClient and Nette framework IMailer 
 * 
 * If you want to use this mailer by default by Nette, put something like this
 * to your bootstrap.php:
 * Mail::$defaultMailer = 
 *  new SMTPMailer('smtp.server.tld', 25, 'tcp', 'username', 'password');


 * Copyright (c) 2013, Martin Pecka (peci1@seznam.cz)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name Martin Pecka nor the
 *       names of contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


 * @package nSMTPMailer
 * @version 2.0.0
 * @copyright (c) 2013 Martin Pecka
 * @author Martin Pecka <peci1@seznam.cz> 
 * @license See license.txt
 */
class SmtpMailer implements \Nette\Mail\IMailer
{

    /** @var SMTPClient The SMTP client to use */
    protected $client;

    /**
     * Create new mailer instance - setup the SMTP client
     *
     * Settings for GMail: server smtp.gmail.com, port:transport either
     *  587:tcp or 465:ssl
     *
     * Currently you can use these SMTP auth mechanisms:
     *  PLAIN - you must provide $authId
     *  LOGIN
     *  CRAM-MD5
     *
     * @param string $server The SMTP server address to connect to 
     *                       (without http://)
     * @param int $port The port to connect to (25 generally, 465 for SSL, 
     *                  587 for GMail SMTP)
     * @param string $transport The transport to use ('tcp', 'ssl' mainly)
     * @param string|NULL $username The username to use to login, if needed
     * @param string|NULL $password The password to use to login, if needed
     * @param string|NULL $authId The role to use in PLAIN authentication
     * @param string|NULL $mechanism The mechanism to use (defaults to LOGIN)
     * @param bool $tryUnauthenticated If true, tries to send the message even
     *                                 if authentication fails
     * @param bool $disableTLS It true, do not use TLS even if available
     * @return void
     */
    public function __construct($server, $port = 25, $transport = 'tcp', 
        $username = NULL, $password = NULL, $authId = NULL, 
        $mechanism = NULL, $tryUnauthenticated = TRUE, $disableTLS = FALSE)
    {
        $this->client = new SMTPClient();
        $this->client->setConnectionInfo($server, $port, $transport, 300, $disableTLS);
        if ($username != NULL) //intentionally !=
            $this->client->setLoginInfo($username, $password, $authId,
                $mechanism, $tryUnauthenticated);
    }

    /**
     * Sends e-mail.
     *
     * Implementation of IMailer
	 * @param  Message The mail to send (instance of Nette\Message)
     * @return void
     *
     * @throws InvalidStateException if something went wrong
	 */
    function send(\Nette\Mail\Message $mail) {

        $from = $mail->getHeader('From');
        //intentionally !=
        $from = ($from != NULL && !empty($from) ? array_keys($from) : NULL);
        if ($from !== NULL)
            $this->client->setFrom($from[0]);

        $to = $mail->getHeader('To');
        $cc = $mail->getHeader('Cc');
        $bcc = $mail->getHeader('Bcc');
        $mail->setHeader('Bcc', NULL);

        $recipients = array();
        //intentionally !=
        ($to != NULL && !empty($to)) ? 
            $recipients = array_merge($recipients, array_keys($to)) : NULL;
        ($cc != NULL && !empty($cc)) ? 
            $recipients = array_merge($recipients, array_keys($cc)) : NULL;
        ($bcc != NULL && !empty($bcc)) ? 
            $recipients = array_merge($recipients, array_keys($bcc)) : NULL;

        $this->client->setRecipients($recipients);

        $this->client->setBody($mail->generateMessage());

        //throws InvalidStateException
        $this->client->send();
    }

    /**
     * Returns array of email addresses the delivery failed for 
     * 
     * @return array of string Email addresses the mail was not delivered to
     */
    public function getUndeliveredRecipients()
    {
        return $this->client->getUndeliveredRecipients();
    }
}

/* ?> omitted intentionally */

