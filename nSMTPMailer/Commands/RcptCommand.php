<?php

//namespace Nette\Mail\SMTPClient

/**
 * The command that adds a recipient to the mail message
 *
 * RFC DESCRIPTION:
 *
 * This command is used to identify an individual recipient of the mail
 * data; multiple recipients are specified by multiple use of this
 * command.  The argument field contains a forward-path and may contain
 * optional parameters.
 *
 * The forward-path normally consists of the required destination
 * mailbox.  Sending systems SHOULD not generate the optional list of
 * hosts known as a source route.
 *
 * 250 Requested mail action okay, completed
 * 251 User not local; will forward to <forward-path>
 *
 * 450 Requested mail action not taken: mailbox unavailable
 *  (e.g., mailbox busy)
 * 451 Requested action aborted: local error in processing
 * 452 Requested action not taken: insufficient system storage
 * 
 * 550 Requested action not taken: mailbox unavailable 
 *  (e.g., mailbox not found, no access, or command rejected 
 *  for policy reasons)
 * 551 User not local; please try <forward-path>
 * 552 Requested mail action aborted: exceeded storage allocation
 * 553 Requested action not taken: mailbox name not allowed
 *  (e.g., mailbox syntax incorrect)


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

class /*Nette\Mail\SMTPClient\*/RcptCommand extends 
    /*Nette\Mail\SMTPClient\*/BaseCommand
{

    /** @var array of string|int Array of response code masks of codes that 
     * should force the application to immediately close the connection 
     * and quit
     * */
    protected $failResponses = 
        array('450', '451', '452', '550', '551', '552', '553');

    protected $successResponses = array('250', '251');

    /** @var string Textual name of the command */
    protected $name = 'RCPT TO';

    /** @var string The command to send - if it is NULL, no command is sent
     *              and the communicator just reads the server response */
    protected $command = 'RCPT TO: <%s>';    

    /** @var array of array The recipients' email addresses 
     * Structure of the array:
     *      'email' => The email address
     *      'retries' => The remaining # of retries to send
     *      'original_email' => The original email, if this is a forwarded 
     *          one */
    protected $recipients = array();

    /**
     * Execute the command, handle error states and throw InvalidStateException
     * if an unrecoverable error occurs. Should be overridden, but always call
     * parent::execute() !!!
     * 
     * @return array of key=>val:
     *      undelivered => Array of addresses the mail couldn't be delivered to
     *      toDeliver => Array of internal format of the addresses not yet 
     *          tried to send the email to. If set, causes the client to repeat
     *          the message sending procedure with these recipients (possible
     *          infinte loop - be sure this array always gets smaller than 
     *          before)
     *      recipientsCount => Number of the recipients the message will really
     *          be sent to
     *
     * @throws InvalidStateException If an unrecoverable error occurs
     */
    public function execute()
    {

        $undeliveredRecipients = array();
        $recipientsCount = 0;

        while (count($this->recipients) >= 1) {

            //send the command and read the response
            parent::execute();

            $recipient = array_pop($this->recipients);

            switch ($this->response->getCode()) {

            case '250':
            case '251':
                if (isset($recipient['original_email'])) {
                    //we finally delivered mail to a user that provided a 
                    //forward-address
                    $key = array_search($recipient['original_email'],
                        $undeliveredRecipients);

                    if ($key !== FALSE)
                        unset($undeliveredRecipients[$key]);
                }

                $recipientsCount++;
                break;

            case '450':
            case '451':
                //add the recipient to the begginning of the queue to try again
                //later
                if ($recipient['retries'] > 0) {
                    $recipient['retries']--;
                    array_unshift($this->recipients, $recipient);
                }
                break;

            /* Handle too much recipients by splitting the message to chunks
             *
             * RFC 821 [30] incorrectly listed the error where an SMTP server
             * exhausts its implementation limit on the number of RCPT commands
             * ("too many recipients") as having reply code 552.  The correct 
             * reply code for this condition is 452.  Clients SHOULD treat a 
             * 552 code in this case as a temporary, rather than permanent, 
             * failure so the logic below works.                
             */
            case '452':
            case '552':
                array_push($this->recipients, $recipient);

                $result['undelivered'] = $undeliveredRecipients;
                //this causes the client to append a new mail sending sequence 
                //for the rest of recipients
                $result['toDeliver'] = $this->recipients;
                $result['recipientsCount'] = $recipientsCount;

                return $result;
                break; //unreachable

            case '550':
            case '553':
                $undeliveredRecipients[] = $recipient['email'];
                break;

            case '551':
                if (preg_match('/<\([^>]*\)>/', $this->response, $matches)) {

                    $forward = array();
                    $forward['email'] = $matches[1];
                    $forward['retries'] = 2;
                    if (isset($recipient['original_email'])) {
                        $forward['original_email'] = 
                            $recipient['original_email'];
                    } else {
                        $forward['original_email'] = $recipient['email'];
                        $undeliveredRecipients[] = $recipient['email'];
                    }

                    $this->recipients[] = $forward;
                }
                break;
            }

        }

        return array(
            'undelivered' => $undeliveredRecipients, 
            'recipientsCount' => $recipientsCount,
        );
    }

    /**
     * Add a recipient's address 
     * 
     * @param string $recipient The recipient's address
     * @return void
     *
     * @throws InvalidArgumentException If the email address is invalid
     */
    public function addRecipient($recipient)
    {
        if (!$this->validateEmail($recipient))
            throw new InvalidArgumentException(
                'The email address provided to RCPT TO was invalid.' .
                'The address was: ' . $recipient);

        $this->recipients[] = array('email' => $recipient, 'retries' => 2);
    }

    /**
     * Add some recipients' addresses
     * 
     * @param array of string $recipients The recipients' addresses
     *
     * @return array of string The recipients that have invalid address format
     */
    public function addRecipients(array $recipients)
    {
        $bad = array();

        if (!empty($recipients)) {
            foreach ($recipients as $recipient) {
                try {
                    $this->addRecipient($recipient);
                } catch (InvalidArgumentException $e) {
                    $bad[] = $recipient;
                }
            }
        }

        return $bad;
    }

    /**
     * Set the internal recipients array to the one given
     *
     * @remarks Used only for sending message chunks, do not use for
     *          another purposes. The array must have the internal
     *          structure.
     * 
     * @param array of array $recipients 
     * @return void
     */
    public function setRecipients(array $recipients)
    {
        $this->recipients = $recipients;
    }

    /**
     * Build the command string and return it (can be overriden)
     * 
     * @return string The command to send to the server
     *
     * @throws InvalidStateException If no recipients are set
     */
    protected function buildCommand()
    {  
        if (count($this->recipients) < 1)
            throw new InvalidStateException(
                'Tried to send mail, but no recipients were set!');

        return sprintf($this->command, 
            $this->recipients[count($this->recipients) - 1]['email']);
    }
}

/* ?> omitted intentionally */
