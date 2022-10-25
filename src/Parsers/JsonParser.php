<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;

use function json_decode;
use function json_last_error;
use function json_last_error_msg;

use const JSON_ERROR_NONE;

class JsonParser extends Parser
{
    /** @inheritDoc */
    protected function parseFile(File $file): array
    {
        $output = json_decode($file->contents(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParseError($file->path(), json_last_error_msg());
        }

        return $output;
    }
}
