<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Contracts;

use Devly\ConfigLoader\Exceptions\FileNotFound;
use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;

interface IParser
{
    /**
     * Parse configuration file to array
     *
     * @param string|File $file Full file path or an instance of `Devly\ConfigLoader\File`
     *
     * @return array<string, mixed> Parsed configuration as array
     *
     * @throws FileNotFound
     * @throws ParseError
     */
    public function parse($file): array;
}
