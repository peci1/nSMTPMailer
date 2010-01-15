<?php

//namespace Nette\Mail\SMTPClient

/**
 * The ESMTP extension command that initiates TLS for the connection
 *
 * RFC 3207 DESCRIPTION:
 *
 * The format for the STARTTLS command is:
 *
 * STARTTLS
 *
 * with no parameters.
 *
 * After the client gives the STARTTLS command, the server responds with
 * one of the following reply codes:
 * 
 *
 * 220 Ready to start TLS
 *
 * 454 TLS not available due to temporary reason
 *
 * 501 Syntax error


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

class /*Nette\Mail\SMTPClient\*/StartTLSCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * indicate command success */
    protected $successResponses = array('220');

    /** @var array of string|int Array of response code masks of codes that 
     * indicate something went wrong, but we can still recover from the fail */
    protected $failResponses = array('454');

    /** @var string Textual name of the command */
    protected $name = 'STARTTLS';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'STARTTLS';    

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
        parent::execute();

        stream_socket_enable_crypto($this->getCommunicator()->getConnection(),
            TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        return NULL;
    }

}

/* ?> omitted intentionally */
