<?php

/*
 * This is an example of use of the nSMTPMailer alone (without Nette)
 * Just substitute username, password a from address and you can send mails!
 */

header('Content-Type: text/html; charset=utf-8');
require_once('nSMTPMailer/SMTPClient.php');

$client = new SMTPClient();

$client->setConnectionInfo('smtp.gmail.com', 587, 'tcp', 300);
$client->setLoginInfo('username', 'password');

$client->setFrom('username@gmail.com');

$recipients = array('user1@seznam.cz', 'user2@seznam.cz');
$client->setRecipients($recipients);

$client->setBody('Very dirty message without headers in it!');

try {
    $client->send();
    $undelivered = $client->getUndeliveredRecipients();
    if (empty($undelivered))
        echo 'Email byl úspěšně odeslán';
    else
        echo 'Email se neodeslal na tyto adresy: ' . implode (',', $undelivered);
} catch (InvalidStateException $e) {
    echo 'Email se vůbec nepovedlo odeslat';
}

/* ?> omitted intentionally */
