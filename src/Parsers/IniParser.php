<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;

use function error_get_last;
use function is_array;
use function parse_ini_file;

class IniParser extends Parser
{
    /** @inheritDoc */
    protected function parseFile(File $file): array
    {
        $output = @parse_ini_file($file->path(), true);

        if (! is_array($output)) {
            $err = error_get_last();

            throw new ParseError($file->path(), $err['message']);
        }

        return $output;
    }
}
