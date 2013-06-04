<?php

namespace Nette;

/*
 * These exceptions are needed for standalone usage of the SMTPClient library
 */

/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 */
class InvalidStateException extends \RuntimeException {}

/**
 * The exception that is thrown when an I/O error occurs.
 */
class IOException extends \RuntimeException {}

/* ?> omitted intentionally */
