<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command sent to the server when ending the communication
 *
 * RFC DESCRIPTION:
 *
 * An SMTP connection is terminated when the client sends a QUIT
 * command.  The server responds with a positive reply code, after which
 * it closes the connection.
 *
 * An SMTP server MUST NOT intentionally close the connection except:
 * -  After receiving a QUIT command and responding with a 221 reply.
 * -  After detecting the need to shut down the SMTP service and
 *    returning a 421 response code.  This response code can be issued
 *    after the server receives any command or, if necessary,
 *    asynchronously from command receipt (on the assumption that the
 *    client will receive it after the next command is issued).
 * 
 * 221 <domain> Service closing transmission channel


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

class /*Nette\Mail\SMTPClient\*/QuitCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * should force the application to immediately close the connection 
     * and quit */
    protected $crashResponses = array();

    /** @var array of string|int Array of response code masks of codes that 
     * indicate command success */
    protected $successResponses = array('221');

    /** @var string Textual name of the command */
    protected $name = 'Quit';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'QUIT';    

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

        //we only expect 221 response, any other is not acceptable
        if (!$this->response->isOfType($this->successResponses)) {
            throw new InvalidStateException(
                'Connection to the SMTP server could not be correctly closed. ' .
                'The server provided the following response to the QUIT command:' .
                $this->response
            );
        }

        return NULL;
    }
}

/* ?> omitted intentionally */


