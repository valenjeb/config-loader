<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Exceptions;

use Exception;
use Throwable;

use function sprintf;

class ParseError extends Exception
{
    public function __construct(string $filepath, string $message, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Failed parsing "%s": %s', $filepath, $message), 0, $previous);
    }
}
