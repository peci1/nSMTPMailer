<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command sent to the server right after connection has been established
 *
 * Note that no command is sent, but the server sends a "reply" - this is 
 * handled be sending NULL command to the communicator
 *
 * RFC DESCRIPTION:
 *
 * An SMTP session is initiated when a client opens a connection to a
 * server and the server responds with an opening message.
 *
 * SMTP server implementations MAY include identification of their
 * software and version information in the connection greeting reply
 * after the 220 code, a practice that permits more efficient isolation
 * and repair of any problems.  Implementations MAY make provision for
 * SMTP servers to disable the software and version announcement where
 * it causes security concerns.  While some systems also identify their
 * contact point for mail problems, this is not a substitute for
 * maintaining the required "postmaster" address (see section 4.5.1).
 *
 * The SMTP protocol allows a server to formally reject a transaction
 * while still allowing the initial connection as follows: a 554
 * response MAY be given in the initial connection opening message
 * instead of the 220.  A server taking this approach MUST still wait
 * for the client to send a QUIT (see section 4.1.1.10) before closing
 * the connection and SHOULD respond to any intervening commands with
 * "503 bad sequence of commands".  Since an attempt to make an SMTP
 * connection to such a system is probably in error, a server returning
 * a 554 response on connection opening SHOULD provide enough
 * information in the reply text to facilitate debugging of the sending
 * system.
 * 
 * 220 <domain> Service ready
 *
 * 554 Transaction failed  (Or, in the case of a connection-opening
 *  response, "No SMTP service here")


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

class /*Nette\Mail\SMTPClient\*/InitializeCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * should force the application to immediately close the connection 
     * and quit */
    protected $crashResponses = array('554');

    /** @var array of string|int Array of response code masks of codes that 
     * indicate command success */
    protected $successResponses = array('220');

    /** @var string Textual name of the command */
    protected $name = 'Initialization';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = NULL;    

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

        //we only expect 220 response, any other non-5xy is not acceptable
        if (!$this->response->isOfType($this->successResponses)) {
            throw new InvalidStateException(
                'The server\' s response to command ' . $this->name . 
                'was not recognized by the client. The response was: ' .
                $this->response
            );
        }

        return NULL;
    }
}

/* ?> omitted intentionally */

