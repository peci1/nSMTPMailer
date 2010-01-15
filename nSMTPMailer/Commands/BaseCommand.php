<?php

//namespace Nette\Mail\SMTPClient

/**
 * The base for all SMTP commands 


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
 * @version 1.0.0
 * @copyright (c) 2009 Martin Pecka (Clevis)
 * @author Martin Pecka <peci1@seznam.cz> 
 * @license See license.txt
 */

abstract class /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * should force the application to immediately close the connection 
     * and quit */
    protected $crashResponses = array('5', '421');

    /** @var array of string|int Array of response code masks of codes that 
     * indicate command success */
    protected $successResponses = array('250');

    /** @var array of string|int Array of response code masks of codes that 
     * indicate something went wrong, but we can still recover from the fail */
    protected $failResponses = array();
    
    /** @var string Textual name of the command */
    protected $name = '';

    /** @var SMTP\Response The response to this command */
    protected $reponse = NULL;

    /** @var string The command to send */
    protected $command = NULL;

    /** @var array of string List of ESMTP extensions this command is 
     *                       interested in */
    protected $acceptedExtensions = array();

    /** @var Nette\Mail\SMTPClient\Communicator The communicator used for 
     *                                          querying the server */
    private $communicator = NULL;

    /**
     * Just sets member variables 
     * 
     * @param Communicator $communicator The communicator used for querying 
     *                                   the server
     * @return void
     */
    public function __construct(
        /*Nette\Mail\SMTPMailer*/Communicator $communicator)
    {
        $this->communicator = $communicator;
    }

    /**
     * Execute the command, handle error states and throw InvalidStateException
     * if an unrecoverable error occurs. Should be overridden, but always call
     * parent::execute() !!!
     * 
     * @return NULL|array of key=>val If the command has some interesting 
     *                                output, return it in the assoc. array
     *                                and these values should be then stored
     *                                in the client's data store
     * @throws InvalidStateException If an unrecoverable error occurs
     */
    public function execute()
    {
        $this->getResponse($this->buildCommand());

        //you can exclude one crash response from the list by putting it on
        //another response list
        if ($this->response->isOfType($this->crashResponses) && 
           !$this->response->isOfType($this->failResponses) &&
           !$this->response->isOfType($this->successResponses)
        ) {
            throw new InvalidStateException(
                'The SMTP server sent an error response which cannot be ' . 
                'handled further. The response was: ' . $this->response
            );
        }

        if (!$this->response->isOfType($this->failResponses) && 
            !$this->response->isOfType($this->successResponses)) {
            throw new InvalidStateException(
                'The SMTP server sent an unknown response to the command ' .
                $this->name . '. The response was: ' . $this->response
            );
        }
    }

    /**
     * Return textual name of the command 
     *
     * @return string The textual name of the command
     */
    public final function getName() { return strToUpper($this->name); }

    /**
     * Returns the list of ESMTP extensions this command is interested in
     * 
     * @return array of string
     */
    public final function getAcceptedExtensions() { 
        return $this->acceptedExtensions; }

    /**
     * Do actions connected with the given ESMTP extension (can be overridden)
     * 
     * @param SmtpClient $client The SMTP client instance
     * @param string $extension The extension name
     * @return void
     */
    public function processExtension(SmtpClient $client, $extension) { }

    /**
     * Build the command string and return it (can be overriden)
     * 
     * @return string The command to send to the server
     */
    protected function buildCommand()
    {
        return $this->command;
    }

    /**
     * Send the command and read the server's response. Save the response
     * to class variable and also return it.
     * 
     * @return Response The response from the server.
     */
    protected final function getResponse()
    {
        $this->response = 
            $this->communicator->getResponse($this->buildCommand());
    }
    /**
     * Validates the given email address according to RFC 2822
     * 
     * @param string $email The address to validate
     * @return bool If the email is valid
     *
     * @see http://www.iki.fi/markus.sipila/pub/emailvalidator.php for the regexp
     * @author Markus Sipilä
     */
    protected function validateEmail($email)
    {
        return (eregi("^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$", $email));
    }
    /**
     * Return the communicator used for querying the server
     * 
     * @return Communicator The communicator used for querying the server
     */
    protected function getCommunicator() { return $this->communicator; }
}

/* ?> omitted intentionally */

