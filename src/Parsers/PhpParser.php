<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;
use Throwable;

use function call_user_func;
use function gettype;
use function is_array;
use function is_callable;

class PhpParser extends Parser
{
    /** @inheritDoc */
    protected function parseFile(File $file): array
    {
        try {
            $output = require $file->path();
            if (gettype($output) !== 'array' && is_callable($output)) {
                $output = call_user_func($output);
            }
        } catch (Throwable $e) {
            throw new ParseError($file->path(), $e->getMessage(), $e);
        }

        if (! is_array($output)) {
            throw new ParseError($file->path(), 'PHP file must return an array.');
        }

        return $output;
    }
}
