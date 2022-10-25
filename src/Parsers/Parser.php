<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Contracts\IParser;
use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;

abstract class Parser implements IParser
{
    /** @inheritDoc */
    public function parse($file): array
    {
        $file = $file instanceof File ? $file : new File($file);

        if ($file->isEmpty()) {
            throw new ParseError($file->path(), 'file is empty.');
        }

        return $this->parseFile($file);
    }

    /**
     * Execute configuration file parsing
     *
     * @return array<string, mixed> Parsed configuration as array
     *
     * @throws ParseError
     */
    abstract protected function parseFile(File $file): array;
}
