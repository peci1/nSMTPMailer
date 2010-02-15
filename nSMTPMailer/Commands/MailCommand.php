<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command that sends the mail originator's identity
 *
 * RFC DESCRIPTION:
 *
 * The first step in the procedure is the MAIL command.
 *
 *    MAIL FROM:<reverse-path> [SP <mail-parameters> ] <CRLF>
 *
 * This command tells the SMTP-receiver that a new mail transaction is
 * starting and to reset all its state tables and buffers, including any
 * recipients or mail data.  The <reverse-path> portion of the first or
 * only argument contains the source mailbox (between "<" and ">"
 * brackets), which can be used to report errors (see section 4.2 for a
 * discussion of error reporting).  If accepted, the SMTP server returns
 * a 250 OK reply.  If the mailbox specification is not acceptable for
 * some reason, the server MUST return a reply indicating whether the
 * failure is permanent (i.e., will occur again if the client tries to
 * send the same address again) or temporary (i.e., the address might be
 * accepted if the client tries again later).  Despite the apparent
 * scope of this requirement, there are circumstances in which the
 * acceptability of the reverse-path may not be determined until one or
 * more forward-paths (in RCPT commands) can be examined.  In those
 * cases, the server MAY reasonably accept the reverse-path (with a 250
 * reply) and then report problems after the forward-paths are received
 * and examined.  Normally, failures produce 550 or 553 replies.
 *
 * Historically, the <reverse-path> can contain more than just a
 * mailbox, however, contemporary systems SHOULD NOT use source routing
 * (see appendix C).
 *
 * The optional <mail-parameters> are associated with negotiated SMTP
 * service extensions (see section 2.2).
 *
 * 250 Requested mail action okay, completed
 *
 * 451 Requested action aborted: local error in processing
 * 452 Requested action not taken: insufficient system storage
 * 
 * 503 Bad sequence of commands (only if extensions are used)
 * 550 Requested action not taken: mailbox unavailable 
 *  (e.g., mailbox not found, no access, or command rejected 
 *  for policy reasons)
 * 552 Requested mail action aborted: exceeded storage allocation
 * 553 Requested action not taken: mailbox name not allowed
 *  (e.g., mailbox syntax incorrect)
 *
 * Implemented with the SIZE extension, which adds the following codes:
 *
 * 452 insufficient system storage
 *
 * 552 message size exceeds fixed maximium message size


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

class /*Nette\Mail\SMTPClient\*/MailCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * should force the application to immediately close the connection 
     * and quit
     * */
    protected $failResponses = array('451', '452');

    /** @var string Textual name of the command */
    protected $name = 'MAIL FROM';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'MAIL FROM: <%s>';    

    /** @var string The sender's email address */
    protected $sender = '';

    /** @var array of string List of ESMTP extensions this command is 
     *                       interested in */
    protected $acceptedExtensions = array('SIZE', '8BITMIME');

    /** @var string String used for the SIZE extension */
    protected $sizeExtension = ' SIZE=%u';

    /** @var NULL|int Size of the message to be sent (used by SIZE extension)*/
    protected $emailSize = NULL;

    /** @var string String used for the 8BITMIME extension */
    protected $eightBitMimeExtension = ' BODY=8BITMIME';

    /** @var bool Whether to use 8bitMime transfer encoding */
    protected $use8bitMime = FALSE;

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

        if ($this->response->isOfType($this->failResponses))
            return array('retry' => TRUE);

        return NULL;
    }

    /**
     * Sets the sender's address 
     * 
     * @param string $sender The sender's address
     * @return void
     *
     * @throws InvalidArgumentException If the email address is invalid
     */
    public function setSender($sender)
    {
        if (!$this->validateEmail($sender))
            throw new InvalidArgumentException(
                'The email address provided to MAIL FROM was invalid.' .
                'The address was: ' . $sender);

        $this->sender = $sender;
    }

    /**
     * Sets the estimated email size (used by SIZE extension). Setting the
     * size to NULL will disable using the extension.
     * 
     * @param int|NULL $size The size of the email
     * @return void
     */
    public function setSize($size)
    {
        $this->emailSize = $size;
    }

    /**
     * Sets, if we should use the 8bitMime transfer encoding 
     * 
     * @param bool $allow True if we want to
     * @return void
     */
    public function set8bitMime($allow)
    {
        $this->use8bitMime = $allow;
    }

    /**
     * Do actions connected with the given ESMTP extension (can be overridden)
     * 
     * @param SmtpClient $client The SMTP client instance
     * @param string $extension The extension name
     * @return void
     */
    public function processExtension(SmtpClient $client, $extension) 
    { 
        switch ($extension) {
        case 'SIZE':
            $this->setSize(strlen($client->getBody()));
            break;

        case '8BITMIME':
            $this->set8bitMime(TRUE);
            break;
        }
    }

    /**
     * Build the command string and return it (can be overriden)
     * 
     * @return string The command to send to the server
     *
     * @throws InvalidStateException If no sender is set
     */
    protected function buildCommand()
    {   
        if ($this->sender === '')
            throw new InvalidStateException(
                'Tried to send email, but no sender was set!');     

        $command = sprintf($this->command, $this->sender);

        if ($this->emailSize !== NULL)
            $command .= sprintf($this->sizeExtension, $this->emailSize);

        if ($this->use8bitMime)
            $command .= $this->eightBitMimeExtension;

        return $command;
    }
}

/* ?> omitted intentionally */
