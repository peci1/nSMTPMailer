<?php

//namespace Nette\Mail\SMTPClient

/**
 * The ESMTP extension command that handles client authentization
 *
 * RFC 2554 DESCRIPTION:
 *
 * This document defines an SMTP service extension [ESMTP] whereby an
 * SMTP client may indicate an authentication mechanism to the server,
 * perform an authentication protocol exchange, and optionally negotiate
 * a security layer for subsequent protocol interactions.  This
 * extension is a profile of the Simple Authentication and Security
 * Layer [SASL].
 * 
 * http://www.fehcom.de/qmail/smtpauth.html - very useful resource
 *
 *
 * 235 Authentication successful
 *
 * 334 Sent information accepted, but need some more
 *
 * 454 Temporary authentication failure


 * Copyright (c) 2009, Martin Pecka (peci1@seznam.cz)
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
 * @uses BaseCommand
 * @version 1.0.0
 * @copyright (c) 2009 Martin Pecka (Clevis)
 * @author Martin Pecka <peci1@seznam.cz> 
 * @license See license.txt
 */

class /*Nette\Mail\SMTPClient\*/AuthCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * indicate command success */
    protected $successResponses = array('235', '334');

    /** @var array of string|int Array of response code masks of codes that 
     * indicate something went wrong, but we can still recover from the fail */
    protected $failResponses = array('454');

    /** @var string Textual name of the command */
    protected $name = 'AUTH';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'AUTH';    

    /** @var string The mechanism used to authenticate */
    protected $mechanism = '';

    /** @var array of string Array of username/password in the order they will
     *                       be used by the protocol */
    protected $credentials = array();

    /**
     * Sets the currently used mechanism
     * 
     * @param string $mechanism 
     * @return void
     */
    public function setMechanism($mechanism) { $this->mechanism = $mechanism; }

    /**
     * Sets the login credentials
     * 
     * @param array $credentials The credentials to use
     * @return void
     */
    public function setCredentials(array $credentials) 
    { 
        $this->credentials = $credentials;
    }

    /**
     * Execute the command, handle error states and throw InvalidStateException
     * if an unrecoverable error occurs. Should be overridden, but always call
     * parent::execute() !!!
     * 
     * @return NULL
     * @throws InvalidStateException If an unrecoverable error occurs
     */
    public function execute()
    {
        switch ($this->mechanism) {
            case 'LOGIN':

                if (count($this->credentials) < 2)
                    throw new InvalidStateException(
                        'You must provide username and password when using ' .
                        'LOGIN authentication mechanism');

                $this->successResponses = array('334');

                // -> AUTH LOGIN
                $this->command = 'AUTH LOGIN';
                parent::execute();
                if ($this->response->isOfType($this->failResponses))
                    return array('retry' => TRUE);

                // <- Username:
                // -> username
                $this->command = base64_encode($this->credentials[0]);
                parent::execute();
                if ($this->response->isOfType($this->failResponses))
                    return array('retry' => TRUE);

                $this->successResponses = array('235');

                // <- Password:
                // -> password
                $this->command = base64_encode($this->credentials[1]);
                parent::execute();
                if ($this->response->isOfType($this->failResponses))
                    return array('retry' => TRUE);

                break;

            case 'PLAIN':
                if (count($this->credentials) < 3)
                    throw new InvalidStateException(
                        'You must provide authId, username and password when '.
                        'using PLAIN authentication mechanism');

                $this->successResponses = array('235');

                // -> AUTH PLAIN authId\0username\0password
                $this->command = 'AUTH PLAIN ' .  base64_encode(
                    $this->credentials[0] . "\x00" .
                    $this->credentials[1] . "\x00" .
                    $this->credentials[2]
                );
                parent::execute();
                if ($this->response->isOfType($this->failResponses))
                    return array('retry' => TRUE);

                break;

            case 'CRAM-MD5':
                if (count($this->credentials) < 2)
                    throw new InvalidStateException(
                        'You must provide username and password when using ' .
                        'CRAM-MD5 authentication mechanism');

                $this->successResponses = array('334');

                $this->command = 'AUTH CRAM-MD5';
                parent::execute();
                if ($this->response->isOfType($this->failResponses))
                    return array('retry' => TRUE);

                $challenge = base64_decode(
                    implode(' ', $this->response->getMessage()));

                $this->successResponses = array('235');
                $this->command = base64_encode($this->credentials[0] . ' ' .
                    hash_hmac('md5', $challenge, $this->credentials[1]));
                parent::execute();
                if ($this->response->isOfType($this->failResponses))
                    return array('retry' => TRUE);

                break;

            default:
                throw new InvalidStateException(
                    'Unsupported authentication mechanism: ' . $this->mechanism);
                break;
        }

        return NULL;
    }    

   /**
     * Build the command string and return it (can be overriden)
     * 
     * @return string The command to send to the server
     *
     * @throws InvalidStateException If no authentication mechanism was set
     */
    protected function buildCommand()
    {
        if ($this->mechanism == '') //intentionally ==
            throw new InvalidStateException('You must first set the ' . 
                'authentication mechanism before you try to authenicate');

        return $this->command;
    }    

}

/* ?> omitted intentionally */
