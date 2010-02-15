<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command that sends the mail data
 *
 * RFC DESCRIPTION:
 * 
 * The receiver normally sends a 354 response to DATA, and then treats
 * the lines (strings ending in <CRLF> sequences, as described in
 * section 2.3.7) following the command as mail data from the sender.
 * This command causes the mail data to be appended to the mail data
 * buffer.  The mail data may contain any of the 128 ASCII character
 * codes, although experience has indicated that use of control
 * characters other than SP, HT, CR, and LF may cause problems and
 * SHOULD be avoided when possible.
 *
 * The mail data is terminated by a line containing only a period, that
 * is, the character sequence "<CRLF>.<CRLF>" (see section 4.5.2).  This
 * is the end of mail data indication.  Note that the first <CRLF> of
 * this terminating sequence is also the <CRLF> that ends the final line
 * of the data (message text) or, if there was no data, ends the DATA
 * command itself.  An extra <CRLF> MUST NOT be added, as that would
 * cause an empty line to be added to the message.  The only exception
 * to this rule would arise if the message body were passed to the
 * originating SMTP-sender with a final "line" that did not end in
 * <CRLF>; in that case, the originating SMTP system MUST either reject
 * the message as invalid or add <CRLF> in order to have the receiving
 * SMTP server recognize the "end of data" condition.
 *
 * The custom of accepting lines ending only in <LF>, as a concession to
 * non-conforming behavior on the part of some UNIX systems, has proven
 * to cause more interoperability problems than it solves, and SMTP
 * server systems MUST NOT do this, even in the name of improved
 * robustness.  In particular, the sequence "<LF>.<LF>" (bare line
 * feeds, without carriage returns) MUST NOT be treated as equivalent to
 * <CRLF>.<CRLF> as the end of mail data indication.
 *
 * Receipt of the end of mail data indication requires the server to
 * process the stored mail transaction information.  This processing
 * consumes the information in the reverse-path buffer, the forward-path
 * buffer, and the mail data buffer, and on the completion of this
 * command these buffers are cleared.  If the processing is successful,
 * the receiver MUST send an OK reply.  If the processing fails the
 * receiver MUST send a failure reply.  The SMTP model does not allow
 * for partial failures at this point: either the message is accepted by
 * the server for delivery and a positive response is returned or it is
 * not accepted and a failure reply is returned.  In sending a positive
 * completion reply to the end of data indication, the receiver takes
 * full responsibility for the message (see section 6.1).  Errors that
 * are diagnosed subsequently MUST be reported in a mail message, as
 * discussed in section 4.4.
 *
 * when sending DATA command:
 *  354 OK, send the mail data
 *
 *  451 local error in processing
 *  554 no recipients specified
 *
 * when sending the message data:
 *  250 OK
 *
 *  451 local error in processing
 *  452 Requested action not taken: insufficient system storage
 *
 *  552 Too much mail data
 *  554 no recipients specified


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

class /*Nette\Mail\SMTPClient\*/DataCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @const int The maximal length of a line of data */
    const MAX_LINE_LENGTH = 998;

    protected $initialSuccessResponses = array('354');
    protected $initialFailResponses = array('451');

    protected $dataSuccessResponses = array('250');
    protected $dataFailResponses = array('451', '452');

    /** @var string Textual name of the command */
    protected $name = 'DATA';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'DATA';

    /** @var string The command initially sent before the output is to be 
     *              sent */
    protected $initialCommand = 'DATA';

    /** @var string The message data to be sent */
    protected $data = ''; 

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
        if ($this->data === '')
            throw new InvalidStateException(
                'Trying to send email, but its contents were not specified.' .
                'Please call DataCommand::setData before executing it.');

        $this->command = $this->initialCommand;
        $this->successResponses = $this->initialSuccessResponses;
        $this->failResponses = $this->initialFailResponses;

        parent::execute();

        if ($this->response->isOfType($this->failResponses)) {
            return array('retry' => TRUE);
        }

        $this->command = $this->data;
        $this->successResponses = $this->dataSuccessResponses;
        $this->failResponses = $this->dataFailResponses;

        parent::execute();

        if ($this->response->isOfType($this->failResponses)) {
            return array('retry' => TRUE);
        }

        return NULL;
    }

    /**
     * Set the mail data 
     * 
     * @param string $data 
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    protected function buildCommand()
    {
        if ($this->command == $this->initialCommand) {
            return $this->command;
        }

        //construct command that holds all the mail data

        $command = $this->command;
        //should be done by the mail creating procedure
        //$command = mb_convert_encoding($command, '7BIT', 'UTF-8');        
        $command = preg_split("/\r\n/", $command);

        $wasLongLine = FALSE;
        foreach ($command as $line => $text) {            
            if (strlen($text) > self::MAX_LINE_LENGTH) {
                $command[$line] = str_split($text, self::MAX_LINE_LENGTH);
                $wasLongLine = TRUE;
            } else if (strlen($text) > 0 && $text[0] == '.') 
                $command[$line] = '.' . $text;
        }

        if ($wasLongLine) {
            $tmp = array();
            foreach ($command as $text) {
                if (is_array($text)) {
                    foreach ($text as $line) {
                        if ($line[0] == '.') $line = '.' . $line;
                        $tmp[] = $line;
                    }
                } else {
                    $tmp[] = $text;
                }
            }
            $command = $tmp;
        }

        $command[] = '.';

        return $command;
    }
}

/* ?> omitted intentionally */

