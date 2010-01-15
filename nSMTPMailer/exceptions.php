<?php
/*
 * These exceptions are needed for standalone usage of the SMTPClient library
 */

/**
 * Thrown if the application gets into a bad state 
 * 
 * @uses RuntimeException
 * @package nSMTPMailer (inspired by Nette)
 * @version 1.0.0
 * @copyright (c) 2009 Martin Pecka
 * @author Martin Pecka <peci1@seznam.cz> 
 * @license See license.txt
 */
class InvalidStateException extends RuntimeException {}

/**
 * Thrown if an I/O error occurs 
 * 
 * @uses RuntimeException
 * @package nSMTPMailer (inspired by Nette)
 * @version 1.0.0
 * @copyright (c) 2009 Martin Pecka
 * @author Martin Pecka <peci1@seznam.cz> 
 * @license See license.txt
 */
class IOException extends RuntimeException {}

/* ?> omitted intentionally */
