<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use function class_exists;
use function is_array;

class YamlParser extends Parser
{
    /** @inheritDoc */
    protected function parseFile(File $file): array
    {
        if (! class_exists('Symfony\Component\Yaml\Yaml')) {
            throw new ParseError($file->path(), 'The "Symfony\Component\Yaml\Yaml" component is not available.');
        }

        try {
            $output = Yaml::parse($file->contents());

            if (! is_array($output)) {
                throw new ParseError($file->path(), 'invalid');
            }

            return Yaml::parse($file->contents());
        } catch (ParseException $e) {
            throw new ParseError($file->path(), $e->getMessage());
        }
    }
}
