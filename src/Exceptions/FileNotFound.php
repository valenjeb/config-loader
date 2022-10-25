<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Exceptions;

use Exception;

use function sprintf;

class FileNotFound extends Exception
{
    public function __construct(string $filepath)
    {
        parent::__construct(sprintf('Filepath "%s" does not exist or is not a file.', $filepath));
    }
}
