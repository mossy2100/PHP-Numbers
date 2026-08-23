<?php

declare(strict_types=1);

namespace OceanMoon\Core\Exceptions;

use DomainException;

/**
 * Exception thrown when a string has an invalid format for the desired operation.
 *
 * This exception is used when a value is of the correct type (string) but has an invalid format (e.g. a string that
 * doesn't match an expected pattern).
 *
 * Typical use cases would be fromString() methods or constructors that accept string arguments.
 */
class FormatException extends DomainException
{
}
