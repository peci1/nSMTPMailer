<?php

/**
 * Example presenter for testing nSMTPMailer
 * 
* Copyright (c) 2013, Martin Pecka (peci1@seznam.cz)
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
 * @version 2.0.0
 * @copyright (c) 2013 Martin Pecka
 * @author Martin Pecka <peci1@seznam.cz> 
 * @license See license.txt
 */
class DefaultPresenter extends BasePresenter
{

    /** @var array|NULL The sending result. */
    private $result = NULL;

    /** @var string The SMTP communication log. */
    private $smtpLog = '';

    /**
     * Try to send example mail.
     *
     * @return void
     */
    public function actionDefault()
    { 
	$recipients = array('peci1@seznam.cz');
	$subject = 'Send emails from Nette';
	$body = 'SMTP client working!';

	//enable SMTP debug messages
	Nette\Mail\SMTP\SMTPClient::$debugMode = TRUE;

	$mailer = $this->getContext()->getService('nette.mailer');
	$message = $this->getContext()->createNette__mail();

        $undelivered = array();
        foreach ($recipients as $email) {
            try {
                if (!preg_match('/^\s*$/', $email))
                    $message->addTo($email);
            } catch (InvalidArgumentException $e) {
                $undelivered[] = $email;
            }
        }

        $message->setSubject($subject);
        $message->setBody($body);

	ob_start();
	try {
            $message->send();
            $undelivered = array_merge(
                $undelivered, 
                $mailer->getUndeliveredRecipients());

            if (count($undelivered) > 0)
                $this->result = $undelivered;
            else
                $this->result = TRUE;
        } catch (Nette\InvalidStateException $e) {
            $this->result = FALSE;
	} catch (Nette\IOException $e) {
            $this->result = FALSE;
	}

	$this->smtpLog = ob_get_clean();
    }

    /**
     * Write the response
     * 
     * @return void
     */
    public function renderDefault()
    {
        $this->template->result = is_array($this->result) ? 
            'Email failed to send to the listed addresses: ' . implode(',', $this->result) : 
	    ($this->result ? 'Email successfully sent' : 'Email has not been sent due to an error');
	$this->template->smtpLog = $this->smtpLog;
    }
}

/* ?> omitted intentionally */
