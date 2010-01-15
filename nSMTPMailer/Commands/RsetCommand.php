<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command that resets the session state to the state after the
 * connection was established
 *
 * RFC DESCRIPTION:
 *
 * This command specifies that the current mail transaction will be
 * aborted.  Any stored sender, recipients, and mail data MUST be
 * discarded, and all buffers and state tables cleared.  The receiver
 * MUST send a "250 OK" reply to a RSET command with no arguments.  A
 * reset command may be issued by the client at any time.  It is
 * effectively equivalent to a NOOP (i.e., if has no effect) if issued
 * immediately after EHLO, before EHLO is issued in the session, after
 * an end-of-data indicator has been sent and acknowledged, or
 * immediately before a QUIT.  An SMTP server MUST NOT close the
 * connection as the result of receiving a RSET; that action is reserved
 * for QUIT (see section 4.1.1.10).
 *
 * Since EHLO implies some additional processing and response by the
 * server, RSET will normally be more efficient than reissuing that
 * command, even though the formal semantics are the same.
 *
 * There are circumstances, contrary to the intent of this
 * specification, in which an SMTP server may receive an indication that
 * the underlying TCP connection has been closed or reset.  To preserve
 * the robustness of the mail system, SMTP servers SHOULD be prepared
 * for this condition and SHOULD treat it as if a QUIT had been received
 * before the connection disappeared.
 *
 * 250 OK


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

class /*Nette\Mail\SMTPClient\*/RsetCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var string Textual name of the command */
    protected $name = 'RSET';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'RSET';    

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

        return NULL;
    }

}

/* ?> omitted intentionally */
