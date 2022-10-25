<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Exceptions;

use Exception;

use function sprintf;

class DirectoryNotFound extends Exception
{
    public function __construct(string $dirPath)
    {
        parent::__construct(sprintf('Directory path "%s" does not exist or is not a directory.', $dirPath));
    }
}
