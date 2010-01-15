<?php

//namespace Nette\Mail\SMTPClient

/**
 * This is a response from the SMTP server


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

class /*Nette\Mail\SMTPClient\*/Response {

    /** @var int The response code */
    protected $code;

    /** @var array of string The message from the server (if it is multiline,
     * contains each line (without response code) in one array item) */
    protected $message;

    /**
     * Just set member variables and check the values are correct 
     * 
     * @param int $code The response code
     * @param array of string $message The response message
     * @return void
     */
    public function __construct($code, array $message) {
        if (!is_numeric($code))
            throw new InvalidArgumentException('SMTP response code must be a '.
                'number, but (' . getType($code) . ')' . $code . ' given.');

        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Return the response code
     *
     * @return int The response code 
     */
    public function getCode() { return $this->code; }

    /**
     * Return the response message
     *
     * @return array The response message
     */
    public function getMessage() { return $this->message; }

    /**
     * Returns true if the response matches the given type
     * 
     * @param string|array of string $type The type to match (can be a 
     *                                     left-part of the response code - 
     *                                     eg. '5', '52', '420'...)
     * @return bool Whether the response matches the given type
     */
    public function isOfType($type)
    {
        if (!is_array($type))
            $type = array($type);

        $isOfType = FALSE;

        foreach ($type as $t) {
            $isOfType = $isOfType || 
                (substr($this->code, 0, strlen($t)) == $t); //intentionally ==
        }

        return $isOfType; 
    }

    /**
     * Return a readable representation of the server response. 
     * 
     * @return void
     */
    public function __toString() {
        return $this->code . ': ' . implode("\r\n", $this->message);
    }
}

/* ?> omitted intentionally */

