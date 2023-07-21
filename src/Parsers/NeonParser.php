<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;
use Nette\Neon\Neon;
use RuntimeException;
use Throwable;

use function class_exists;

class NeonParser extends Parser
{
    /** @inheritDoc */
    protected function parseFile(File $file): array
    {
        if (! class_exists('Nette\Neon\Neon')) {
            throw new RuntimeException(
                'The nette/neon extension must be installed in order to parse a `.neon` files.'
            );
        }

        try {
            return Neon::decodeFile($file->path());
        } catch (Throwable $e) {
            throw new ParseError($file->path(), $e->getMessage());
        }
    }
}
