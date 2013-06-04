<?php

/*
 * This is an example of use of the nSMTPMailer alone (without Nette)
 * Just substitute username, password a from address and you can send mails!
 */

header('Content-Type: text/html; charset=utf-8');
require_once('nSMTPMailer/SMTPClient.php');

$client = new Nette\Mail\SMTP\SMTPClient();

$client->setConnectionInfo('smtp.gmail.com', 587, 'tcp', 300);
$client->setLoginInfo('username', 'password');

$client->setFrom('user@gmail.com');

$recipients = array('target1@seznam.cz', 'target2@seznam.cz');
$client->setRecipients($recipients);

// do not forget to include an empty line between headers and message body!!!
$client->setBody(<<<END
Subject: Sending mails using nSMTPMailer

This mail contains very useful information.
END
);

try {
    $client->send();
    $undelivered = $client->getUndeliveredRecipients();
    if (empty($undelivered))
        echo 'Email successfully sent';
    else
        echo 'Email failed to send to the listed addresses: ' . implode (',', $undelivered);
} catch (Nette\InvalidStateException $e) {
    echo 'Email has not been sent due to an error';
} catch (Nette\IOException $e) {
    echo 'Email has not been sent due to an error';
}

/* ?> omitted intentionally */
