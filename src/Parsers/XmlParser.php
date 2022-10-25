<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;

use function array_pop;
use function function_exists;
use function json_decode;
use function json_encode;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function simplexml_load_file;

use const LIBXML_NOERROR;

class XmlParser extends Parser
{
    /** @inheritDoc */
    protected function parseFile(File $file): array
    {
        if (! function_exists('libxml_use_internal_errors')) {
            $message = 'The \'ext-simplexml\' is missing. It is required for parsing XML files.';

            throw new ParseError($file->path(), $message);
        }

        if (! function_exists('libxml_get_errors')) {
            $message = 'The \'ext-libxml\' is missing. It is required for parsing XML files.';

            throw new ParseError($file->path(), $message);
        }

        libxml_use_internal_errors(true);
        $data = simplexml_load_file($file->path(), 'SimpleXMLElement', LIBXML_NOERROR);

        if ($data === false) {
            $errors      = libxml_get_errors();
            $latestError = array_pop($errors);

            throw new ParseError($file->path(), $latestError->message);
        }

        return json_decode(json_encode($data), true);
    }
}
