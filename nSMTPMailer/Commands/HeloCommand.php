<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command sent to the server after connection initialization
 *
 * RFC DESCRIPTION:
 *
 * Once the server has sent the welcoming message and the client has
 * received it, the client normally sends the EHLO command to the
 * server, indicating the client's identity.  In addition to opening the
 * session, use of EHLO indicates that the client is able to process
 * service extensions and requests that the server provide a list of the
 * extensions it supports.  Older SMTP systems which are unable to
 * support service extensions and contemporary clients which do not
 * require service extensions in the mail session being initiated, MAY
 * use HELO instead of EHLO.  Servers MUST NOT return the extended
 * EHLO-style response to a HELO command.  For a particular connection
 * attempt, if the server returns a "command not recognized" response to
 * EHLO, the client SHOULD be able to fall back and send HELO.
 *
 * In the EHLO command the host sending the command identifies itself;
 * the command may be interpreted as saying "Hello, I am <domain>" (and,
 * in the case of EHLO, "and I support service extension requests").
 *
 * 250 Requested mail action okay, completed
 *
 * 500 Syntax error, command unrecognized (if EHLO not supported)
 *  (This may include errors such as command line too long)
 * 504 Command parameter not implemented
 * 550 Requested action not taken: mailbox unavailable
 *  (e.g., mailbox not found, no access, or command rejected
 *  for policy reasons)


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

class /*Nette\Mail\SMTPClient\*/HeloCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * indicate something went wrong, but we can still recover from the fail */
    protected $failResponses = array('500', '501', '502');

    /** @var string Textual name of the command */
    protected $name = 'EHLO/HELO';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'EHLO';    

    /**
     * Execute the command, handle error states and throw InvalidStateException
     * if an unrecoverable error occurs. Should be overridden, but always call
     * parent::execute() !!!
     * 
     * @return NULL|array:
     *      extensions => Array of supported server extensions
     * @throws InvalidStateException If an unrecoverable error occurs
     */
    public function execute()
    {
        $this->command = 'EHLO';
        $this->failResponses = array('500');
        parent::execute();

        if ($this->response->isOfType('500')) {
            //EHLO was not recognized, so try HELO
            $this->command = 'HELO';
            $this->failResponses = array();
            parent::execute();
            return NULL;
        } else {
            $extensions = $this->response->getMessage();
            array_shift($extensions);
            return array('extensions' => $extensions);   
        }        
    }

    /**
     * Build the command string and return it (can be overriden)
     * 
     * @return string The command to send to the server
     */
    protected function buildCommand()
    {
        $domainString = $_SERVER['SERVER_NAME'];
        return $this->command . ' ' . $domainString;
    }
}

/* ?> omitted intentionally */
