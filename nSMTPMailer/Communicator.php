<?php

//namespace Nette\Mail\SMTPClient

/**
 * A class that communicates with the server, sends commands and receives
 * responses


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

class /*Nette\Mail\SMTPClient\*/Communicator
{

    /** @const int Number of retries if the connection failed */
    const MAX_CONNECTION_RETRIES = 3;

    /** @const int The delay between two connection attempts (in seconds) */
    const CONNECTION_RETRY_DELAY = 5;

    /** @const int The maximal length of single response line in characters */
    const MAX_RESPONSE_LENGTH = 515;

    /** @const int The maximal number of lines in a server response 
     * (basically used for infinte cycles detection)*/
    const MAX_RESPONSE_LINES = 100;    

    /** @const string The string representing end of line */
    const EOL = "\r\n";

    /** @var Handle of the connection to the server. */
    protected $connection = NULL;

    /** @var string The transport used for the connection (one of 
     * stream_get_transports() values - usually tcp, tls, ssl) */
    protected $transport = 'tcp';

    /** @var string The host to connect to */
    protected $host = '127.0.0.1';

    /** @var int The port to connect to */
    protected $port = 25;

    /** @var int The connection timeout in seconds */
    protected $timeout = 300;

    /** @var SMTP\Response The last response read from the server */
    protected $lastResponse = NULL;

    /**
     * Create the communicator with the given credentials 
     * 
     * @param string $host The host to connect to
     * @param int $port The port to connect to
     * @param string $transport The transport used for the connection (one of 
     *      stream_get_transports() values - usually tcp, tls, ssl) 
     * @param int $timeout The connection timeout in seconds 
     * @return void
     *
     * @throws InvalidArgumentException If one of the arguments is invalid
     */
    public function __construct($host, $port, $transport, $timeout = 300)
    {
        $this->setHost($host);
        $this->setPort($port);
        $this->setTransport($transport);
        $this->setTimeout($timeout);
    }

    /**
     * Connects to the given server and stores the connection in 
     * $this->connection. If the connection fails, tries up to 
     * self::MAX_CONNECTION_RETRIES reconnections.
     *
     * Requires the transport, host, port and timeout to be correctly set
     *
     * @return void
     *
     * @throws InvalidStateException If the connection failed
     */
    public function connect()
    {

        for ($i = 0; $i < self::MAX_CONNECTION_RETRIES; $i++) {
            SMTPClient::debug(
                'Connection attempt #%u to %s://%s:%u with timeout %u seconds',
                $i, $this->transport, $this->host, $this->port, 
                $this->timeout);

            $this->connection = @fsockopen(
                $this->transport . '://' . $this->host,
                $this->port,
                $errorNumber,
                $errorMessage,
                $this->timeout);

            if ($this->connection !== FALSE)
                return;

            SMTPClient::debug(
                'Connection attempt #%u failed with code %u and message %s',
                $i, $errorNumber, $errorMessage);

            if ($i < self::MAX_CONNECTION_RETRIES - 1)
                SMTPClient::debug('Will try to connect again in %u seconds',
                    self::CONNECTION_RETRY_DELAY);

            sleep(self::CONNECTION_RETRY_DELAY);
        }

        throw new InvalidStateException(
            'Could not connect to ' . $this->transport.'://'.$this->host . ':'
            . $this->port);
    }

    /**
     * Sends the command to the server and reads the response to it. 
     * 
     * @param string|NULL|array $command The command to send (can use printf 
     *                                   style). If NULL, no command is sent 
     *                                   and this just reads a server response.
     *                                   If it is an array, all items will be 
     *                                   treated like lines of commands and
     *                                   will be sent to the server while the
     *                                   response will be read only after all
     *                                   of them are sent.
     * @param mixed $args,... Optional printf-like arguments
     *
     * @return Response The server response
     *
     * @throws InvalidStateException If the communicator is not connected
     * @throws InvalidArgumentException If printf-like arguments are used for
     *                                  non-textual command (ie. array command)
     * @throws IOException If writing or reading over network failed
     */
    public function getResponse($command)
    {

        if ($this->connection === NULL)
            throw new InvalidStateException('Cannot communicate with SMTP ' . 
                'server when not connected.');

        if ($command !== NULL) {

            if (func_num_args() > 1) {
                if (!is_string($command))
                    throw new InvalidArgumentException(
                        'Cannot use printf-like arguments to a command which' .
                        ' is not a string!');

                $args = func_get_args(); array_shift($args);
                $command = vsprintf($command, $args);
            }

            if (!is_array($command)) $command = array($command);

            foreach ($command as $line) {
                //send the command to the server
                SMTPClient::debug("\n\nCOMMAND: %s\n=======", $line);
                if (@fwrite($this->connection, $line . self::EOL) === FALSE) {
                    if (@fwrite($this->connection, $line . self::EOL) === FALSE) {
                        throw new IOException('Could not send the command to the ' . 
                            'server: ' . $command);
                    }
                }
            }

        }

        /*
         * The reply text may be longer than a single line; in these cases the
         * complete text must be marked so the SMTP client knows when it can
         * stop reading the reply.  This requires a special format to indicate a
         * multiple line reply.
         *
         * The format for multiline replies requires that every line, except the
         * last, begin with the reply code, followed immediately by a hyphen,
         * "-" (also known as minus), followed by text.  The last line will
         * begin with the reply code, followed immediately by <SP>, optionally
         * some text, and <CRLF>.  As noted above, servers SHOULD send the <SP>
         * if subsequent text is not sent, but clients MUST be prepared for it
         * to be omitted.
         *
         * For example:
         *
         *    123-First line
         *    123-Second line
         *    123-234 text beginning with numbers
         *    123 The last line
         *
         * In many cases the SMTP client then simply needs to search for a line
         * beginning with the reply code followed by <SP> or <CRLF> and ignore
         * all preceding lines.  In a few cases, there is important data for the
         * client in the reply "text".  The client will be able to identify
         * these cases from the current context.
         */

        $response['code'] = '';
        $response['text'] = array();

        $responsePart = '';

        $i=0;
        //we fetch lines of the response
        while ($line = fgets($this->connection, self::MAX_RESPONSE_LENGTH)) {

            //fgets leaves the CRLF at the end of the string
            $line = rtrim($line);

            //blank line shouldn't appear, it indicates nothing more is to
            //be read
            if (strlen($line) < 3)
                break;

            //if we loop a lot of times, something went wrong, so
            //give up the reading
            if ($i > self::MAX_RESPONSE_LINES)
                throw new InvalidStateException(
                    'There was a problem reading server response. The client' .
                    'could not detect its end.');

            SMTPClient::debug('RESPONSE LINE #%u: %s', ++$i, $line);

            //first 3 charachters denote the response code
            $response['code'] = substr($line, 0, 3);

            //and everything after the 4th character is the response text
            //note that the response text can be empty, in that case the
            //response text will be false ( @see substr() )
            $response['text'][] = substr($line, 4);

            //If we encountered a response, where space follows response code,
            //it is the last line. Other lines have a hyphen (-) instead
            if(substr($line, 3, 1) == ' ')
                break; 

        }

        $this->lastResponse = 
            new Response((int)$response['code'], $response['text']);
        return $this->lastResponse;
    }

    /**
     * Sets the transport and checks the value to be correct 
     * 
     * @param string $transport The new transport value (one of 
     *                          stream_get_transports() values)
     *
     * @return Communicator Provides fluent interface
     *
     * @throws InvalidArgumentException If the transport is empty
     */
    public function setTransport($transport)
    {
        if ($transport == '') //intentionally ==
            throw new InvalidArgumentException(
                'Invalid value of the SmtpClient transport: ' . $transport);

        if (!in_array($transport, stream_get_transports())) 
            throw new InvalidArgumentException(
                'Transport layer ' . $transport . ' is not supported by ' .
                'the local PHP installation.');

        $this->transport = $transport;
        return $this;
    }

    /**
     * Sets the host and checks the value to be correct 
     * 
     * @param string $host The new host value
     * @return Communicator Provides fluent interface
     *
     * @throws InvalidArgumentException If the host is empty
     */
    public function setHost($host)
    {
        if ($host == '') //intentionally ==
            throw new InvalidArgumentException(
                'Invalid value of the SmtpClient host: ' . $host);

        $this->host = $host;
        return $this;
    }

    /**
     * Sets the port and checks the value to be correct 
     * 
     * @param int $port The new port value
     * @return Communicator Provides fluent interface
     *
     * @throws InvalidArgumentException If the port is not a number
     */
    public function setPort($port)
    {
        if ($port == '' || !is_numeric($port)) //intentionally ==
            throw new InvalidArgumentException(
                'Invalid value of the SmtpClient port: ' . $host);

        $this->port = $port;
        return $this;
    }
    
    /**
     * Sets the connection timeout and checks the value to be correct
     * 
     * @param int $timeout The new timeout value
     * @return Communicator Provides fluent interface
     *
     * @throws InvalidArgumentException If the timeout is not a number
     */
    public function setTimeout($timeout)
    {
        if ($timeout == '' || !is_numeric($timeout)) //intentionally ==
            throw new InvalidArgumentException(
                'Invalid value of the SmtpClient timeout: ' . $timeout);

        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Return the last server response 
     * 
     * @return Response The last server response
     */
    public function getLastResponse() { return $this->lastResponse; }

    /**
     * Returns the active connection.
     * 
     * @return The active connection
     */
    public function getConnection() { return $this->connection; }

}

/* ?> omitted intentionally */
