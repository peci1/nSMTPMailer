<?php

namespace Nette\Mail\SMTP;

use \Nette\InvalidStateException;
use \Nette\IOException;

require_once(__DIR__ . '/Communicator.php');
require_once(__DIR__ . '/Response.php');
require_once(__DIR__ . '/Commands/BaseCommand.php');
require_once(__DIR__ . '/Commands/InitializeCommand.php');
require_once(__DIR__ . '/Commands/HeloCommand.php');
require_once(__DIR__ . '/Commands/QuitCommand.php');

spl_autoload_register('\Nette\Mail\SMTP\SMTPClient::autoload');

/**
 * A client for mailing over SMTP with various ESMTP extensions
 *
 * Intended to be used with Nette framework (mainly its Message class), but can be
 * used as a standalone mailer too


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

class SmtpClient
{

    /** @var bool Whether to write out the debugging info */
    public static $debugMode = FALSE;

    /** @var string The username to login with (leave empty if you 
     *              don't want to login) */
    protected $username = '';
    /** @var string The password to login with (leave empty if you 
     *              don't want to login) */
    protected $password = '';
    /** @var string The role to login with (leave empty if you 
     *              don't want to login) (used by PLAIN mechanism) */
    protected $authId = '';

    /** @var boolean If true, don't use TLS even if available */
    protected $disableTLS = FALSE;

    /** @var string|NULL The preffered autentication mechanism name */
    protected $preferredAuthenticationMechanism = NULL;

    /** @var bool If true, try to send the message even if authentication 
     *            fails */
    protected $tryUnauthenticated = TRUE;

    /** @var string The email sender's address */
    protected $from = '';

    /** @var array of string List of email recipients */
    protected $recipients = array();

    /** @var string Body of the email (with headers and everything else!) */
    protected $body = '';

    /** @var array Associative array of some data from the commands (eg. list 
     *             of server extensions and so on)*/
    protected $data = array();

    /** @var array This is used as the queue for the commands that have to be
     *             sent to the SMTP server */
    protected $commandQueue = array();

    /** @var Nette\Mail\SMTP\Communicator The communicator used for 
     *                                          communication with 
     *                                          the SMTP server */
    protected $communicator = NULL;

    /** @var array of string Array of the addresses the mail could not be 
     *                       delivered to */
    protected $undeliveredTo = array();

    /** @var int The number of recipients the mail was successfully sent to */
    protected $successfulRecipientsCount = 0;

    /** @var int The number of retries if RCPT causes re-initialization of the
     *           session (eg. >100 recipients specified) */
    protected $recipientChunkRetries = 2;

    /** @var int The number of retries when temporary server error detected */
    protected $retries = 2;

    /**
     * Tries to load classes as needed
     * 
     * @param string $classWithNS Name of the class to load with namespace
     * @return void
     */
    public static function autoload($classWithNS) {
	//remove namespace
	$nameParts = explode('\\', $classWithNS);
	$class = $nameParts[sizeof($nameParts)-1];

        if (file_exists(__DIR__ . '/' . $class . '.php'))
            require_once(__DIR__ . '/' . $class . '.php');

        if (file_exists(__DIR__ . '/Commands/' . $class . '.php'))
            require_once(__DIR__ . '/Commands/' . $class . '.php');

	// if Nette is running, we do not want to load exceptions.php, since 
	// Nette will load the required exceptions
        if (preg_match('/Exception$/', $class) && !class_exists('Nette\\Application\\Application', false))
            require_once(__DIR__ . '/exceptions.php');
    }

    /**
     * Sets the connection settings needed for the client to work 
     * 
     * @param string $host The host to connect to
     * @param int $port The port to connect to
     * @param string $transport The transport used for the connection (one of 
     *                          stream_get_transports() values - usually 
     *                          tcp, tls, ssl)
     * @param int $timeout The connection timeout in seconds
     * @param bool $disableTLS If true, do not use TLS, even if available
     * @return void
     *
     * @throws InvalidArgumentException If one of the arguments is invalid
     */
    public function setConnectionInfo(
        $host, $port = 25, $transport = 'tcp', $timeout = 300, 
        $disableTLS = FALSE)
    {
        $this->communicator = 
            new Communicator($host, $port, $transport, $timeout);
        $this->disableTLS = $disableTLS;
    }

    /**
     * Do not use TLS even if available.
     *
     * @return void
     */
    public function disableTLS()
    {
	$this->disableTLS = FALSE;
    }

    /**
     * Sets the login info for the connection 
     * 
     * @param string $username The username to use
     * @param string $password The password to use
     * @param string $authId The authorization role to login as
     * @param string|NULL $mechanism The mechanism to use (defaults to LOGIN)
     * @param bool $tryUnauthenticated If true, tries to send the message even 
     *                                 if authentication fails
     *
     * @return void
     */
    public function setLoginInfo($username, $password, $authId = NULL, $mechanism = NULL, $tryUnauthenticated = TRUE)
    {
        $this->username = $username;
        $this->password = $password;
        $this->authId = $authId;
        $this->preferredAuthenticationMechanism = $mechanism;
        $this->tryUnauthenticated = $tryUnauthenticated;
    }

    /**
     * When all settings are done, send the message 
     * 
     * See $this->undeliveredTo for list of addresses the mail was not sent to!
     * The client won't throw an exception if at least one recipient was valid.
     *
     * @return void If no exception is thrown, the mail sending succeeded, but
     *              check $this->undeliveredTo if it is empty after sending
     *
     * @throws InvalidStateException If something went wrong, see the exception
     *                               message for details
     */
    public function send()
    {
        if ($this->communicator === NULL)
            throw new InvalidStateException(
                'You must call SMTPClient::setConnectionInfo before sending ' .
                'a message.'
            );

        //throws InvalidStateException - intentionally uncaught
        $this->communicator->connect();

        try {

            $exception = NULL;

            //definition of the command sequence
            $this->commandQueue[] = new InitializeCommand($this->communicator);
            $this->commandQueue[] = new HeloCommand($this->communicator);
            //get available ESMTP extensions from EHLO response
            $this->processCommandQueue();

            //switch to TLS if available
            if ($this->canUseExtension('STARTTLS') && !$this->disableTLS) {
                $this->commandQueue[] = new StartTLSCommand($this->communicator);
                $this->commandQueue[] = new HeloCommand($this->communicator);
                $this->processCommandQueue();
            }            

            if ($this->canUseExtension('AUTH') && 
                $this->username.$this->password != '' &&
                isset($this->data['auth_mechanisms']) &&
                !empty($this->data['auth_mechanisms'])) {

                $authenticated = $this->authenticate();
                if (!$authenticated && !$this->tryUnauthenticated)
                    throw new InvalidStateException(
                        'SMTPClient failed to authenticate');
            }

            //send the other commands
            $this->buildCommandQueue(NULL, FALSE);
            $this->processCommandQueue();
        } catch (InvalidStateException $e) {
            $exception = $e;
        } catch (IOException $e) {
            $exception = $e;
        }

        //QUIT
        $quitCommand = new QuitCommand($this->communicator);
        try {
            $quitCommand->execute();
        } catch (InvalidStateException $e) {
            //we do not want this exception to rewrite the previous one stored
            //in $exception!!!
            self::debug($e->getMessage());
        }

        if ($exception === NULL) {
            self::debug(
                'The mail was successfully sent to %u recipients out of ',
                'total %u',
                $this->successfulRecipientsCount, count($this->recipients)
            );
        } else {
            self::debug('The sending of the mail failed: ');
            self::debug($exception->getMessage());

            if (!($exception instanceOf InvalidStateException))
                //we only want to throw one type of exception
                throw new InvalidStateException($e->getMessage());
            else
                throw $exception;
        }
    }

    /**
     * If self::$debugMode is true, then prints out the given message 
     * (using printf style)
     * 
     * @param string $message The message to print (i printf format)
     * @param mixed $args,... Optional printf-like arguments
     * @return void
     */
    public static function debug($message)
    {
        if (self::$debugMode) {

            if (func_num_args() > 1) {
                $args = func_get_args();
                array_shift($args);

                $message = vsprintf($message, $args);
            }

            echo $message . "\n";
        }
    }

    /**
     * Sets the email's from address 
     * 
     * @param string $from The from address
     * @return SMTPClient Provides fluent interface
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Sets the array of recipients of the email (including BCC, CC recipients)
     * 
     * @param array of string $recipients The array of recipients
     * @return SMTPClient Provides fluent interface
     */
    public function setRecipients(array $recipients)
    {
        $this->recipients = $recipients;
        return $this;
    }

    /**
     * Adds a recipient to the email recipients list 
     * 
     * @param string $recipient The recipient to add
     * @return SMTPClient Provides fluent interface
     */
    public function addRecipient($recipient)
    {
        $this->recipients[] = $recipient;
        return $this;
    }

    /**
     * Sets the email body (note that this must be the processed text, so 
     * headers and so on must be included in it)
     * 
     * @param string $body The email body
     * @return SMTPClient Provides fluent interface
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Returns the email body 
     * 
     * @return string The email body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns array of all addresses the mail could not be delivered to 
     * 
     * @return array of string The addresses the mail could not be delivered to
     */
    public function getUndeliveredRecipients()
    {
        return $this->undeliveredTo;
    }

    /**
     * Pushes from the command queue and executes the commands read from it
     * until it is empty 
     * 
     * @return void
     *
     * @throws InvalidStateException If something went wrong, see the exception
     *                               message for details
     * @throws IOException
     */
    protected function processCommandQueue()
    {
        while (!empty($this->commandQueue)) {

            $command = array_shift($this->commandQueue);

            //command extension API
            $acceptedExtensions = $command->getAcceptedExtensions();
            if (!empty($acceptedExtensions)) {
                foreach($acceptedExtensions as $extension) {
                    if ($this->canUseExtension($extension))
                        $command->processExtension($this, $extension);
                }
            }

            $data = $command->execute();

            //the return values of the command
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $this->data[$key] = $value;
                    $this->responseDataCallback($key);
                }
            }

        }
    }

    /**
     * Adds a set of commands to the command queue - all the commands after
     * initialization (not including!) needed to send the email. If recipients
     * is not NULL, use it as the array of recipients to deliver to (in the 
     * RcptCommand internal format - see its docs)
     * 
     * @param array|NULL $recipients If not NULL, use it as the array of 
     *                               recipients
     * @param bool $sendHELO If TRUE, add HELO to the beginning, otherwise do 
     *                       not add it
     * @return void
     */
    protected function buildCommandQueue($recipients = NULL, $sendHELO = TRUE)
    {
        //HELO/EHLO
        if ($sendHELO)
            $this->commandQueue[] = new HeloCommand($this->communicator);

        //MAIL FROM
        $mailCommand = new MailCommand($this->communicator);
        $mailCommand->setSender($this->from);
        if ($this->canUseExtension('SIZE')) {
            $mailCommand->setSize(strlen($this->body));
        }
        $this->commandQueue[] = $mailCommand;

        //RCPT TO
        $rcptCommand = new RcptCommand($this->communicator);
        if ($recipients !== NULL) {
            $rcptCommand->setRecipients($this->data['toDeliver']);
        } else {
            if (!empty($this->recipients)) {
                $bad = $rcptCommand->addRecipients($this->recipients);
                //$bad will contain all syntactically bad addresses
                if (!empty($bad))
                    $this->undeliveredTo = 
                        array_merge($bad, $this->undeliveredTo);
            } else {
                throw new InvalidStateException(
                    'Trying to send the mail, but no recipients were set');
            }
        }
        $this->commandQueue[] = $rcptCommand;

        //DATA
        $dataCommand = new DataCommand($this->communicator);
        $dataCommand->setData($this->body);
        $this->commandQueue[] = $dataCommand;
        
    }

    /**
     * Return true if the server supports the given ESMTP extension 
     * 
     * @param string $extension The extension defined by its keyword
     * @return bool true if the server supports the given ESMTP extension
     */
    protected function canUseExtension($extension)
    {
        if (!isset($this->data['extensions']))
            return FALSE;

        foreach ($this->data['extensions'] as $ext) {
            if (substr($ext, 0, strlen($extension)) == $extension)
                return TRUE;
        }

        return FALSE;
    }

    /**
     * Performs actions on some values that we get as the result of executing
     * a command. The values are already saved in $this->data with key $key
     * 
     * @param string $key Name of the response value type
     * @return void
     */
    protected function responseDataCallback($key)
    {
        switch ($key) {

        case 'extensions':
            $mechanisms = array();
            foreach ($this->data['extensions'] as $extension) {
                if (substr($extension, 0, 4) == 'AUTH') {
                    $mechanisms = preg_split('/ /', substr($extension, 5));
                }
            }

            if (!empty($mechanisms)) {
                $this->data['auth_mechanisms'] = $mechanisms;
            }

            break;

        case 'undelivered':
            $this->undeliveredTo = array_merge($this->undeliveredTo, 
                $this->data[$key]);
            break;

        case 'toDeliver':
            if (count($this->data[$key]) >= 100 
                || $this->recipientChunkRetries > 0) {


                $this->commandQueue[] = new RsetCommand($this->communicator);
                $this->buildCommandQueue($this->data[$key]);

            }          

            if (count($this->data[$key]) < 100) {
                $this->recipientChunkRetries--;
            }

            break;

        case 'retry':
            if ($this->data[$key] !== TRUE)
                return;
            
            if ($this->retries > 0) {

                $this->commandQueue[] = new RsetCommand($this->communicator);
                $this->buildCommandQueue();

            }          

            $this->retries--;

            break;

        case 'recipientsCount':
            $this->successfulRecipientsCount += $this->data[$key];
            break;
        }
    }

    /**
     * Perform ESMTP authentication 
     * 
     * @return bool If the authentication succeeded
     */
    protected function authenticate()
    {
        //move the prefferred auth. mechanism to the first position
        //other mechanisms are sorted by the order the server wrote them out
        if ($this->preferredAuthenticationMechanism !== NULL) {
            $toDelete = array_search(
                $this->preferredAuthenticationMechanism, 
                $this->data['auth_mechanisms']);

            if ($toDelete !== FALSE) {
                unset($this->data['auth_mechanisms'][$toDelete]);                        
            }
            $this->data['auth_mechanisms'] = array_merge(
                array(0 => $this->preferredAuthenticationMechanism),
                array_values($this->data['auth_mechanisms'])
            );
        }

        //try all available mechanisms until one of them succeeds
        //in none succeeds, don't throw exception, perhaps the mail could
        //be sent without being authenticated, so give it a try
        $success = TRUE;
        foreach ($this->data['auth_mechanisms'] as $mechanism) {
            try {
                $success = TRUE;
                $authCommand = new AuthCommand($this->communicator);
                $authCommand->setMechanism($mechanism);
                switch ($mechanism) {
                    case 'LOGIN':
                    case 'CRAM-MD5':
                        $authCommand->setCredentials(array(
                            $this->username,
                            $this->password
                        ));
                        break;

                    case 'PLAIN':
                        $authCommand->setCredentials(array(
                            $this->authId,
                            $this->username,
                            $this->password
                        ));
                        break;
                }
                $this->commandQueue[] = $authCommand;
                $this->processCommandQueue();
                break;

            } catch (InvalidStateException $e) {
                //this mechanism hasn't succeeded, so try another one
                $success = FALSE;
            }
        }

        return $success;
    }
}

/* ?> omitted intentionally */
