<?php

declare(strict_types=1);

namespace Devly\ConfigLoader;

use Devly\ConfigLoader\Contracts\IParser;
use Devly\ConfigLoader\Exceptions\FactoryError;
use Devly\ConfigLoader\Exceptions\FileNotFound;
use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\IniParser;
use Devly\ConfigLoader\Parsers\JsonParser;
use Devly\ConfigLoader\Parsers\NeonParser;
use Devly\ConfigLoader\Parsers\PhpParser;
use Devly\ConfigLoader\Parsers\XmlParser;
use Devly\ConfigLoader\Parsers\YamlParser;

use function array_key_exists;
use function class_exists;
use function class_implements;
use function in_array;
use function sprintf;

class ParserFactory
{
    /** @var array|string[] */
    protected array $parsers = [
        'xml' => XmlParser::class,
        'php' => PhpParser::class,
        'json' => JsonParser::class,
        'ini' => IniParser::class,
        'neon' => NeonParser::class,
        'yaml' => YamlParser::class,
    ];

    /** @var IParser[] */
    protected array $instances = [];

    /** @throws FactoryError */
    public function addParser(string $name, string $parserClassName): void
    {
        if (! class_exists($parserClassName)) {
            throw new FactoryError(sprintf('Parser class name "%s" does not exist.', $parserClassName));
        }

        if (! in_array(IParser::class, class_implements($parserClassName))) {
            throw new FactoryError(sprintf('Parser class must implement "%s"', IParser::class));
        }

        $this->parsers[$name] = $parserClassName;
    }

    /**
     * Create a new instance of the specified parser
     *
     * @throws FactoryError if the specified parser type is not supported.
     */
    public function create(string $name): IParser
    {
        if (! array_key_exists($name, $this->parsers)) {
            throw new FactoryError(sprintf('The ".%s" file type is not supported.', $name));
        }

        return new $this->parsers[$name]();
    }

    /**
     * Get an instance of the specified parser
     *
     * @throws FactoryError
     */
    public function getParser(string $name): IParser
    {
        if (! array_key_exists($name, $this->instances)) {
            $this->instances[$name] = $this->create($name);
        }

        return $this->instances[$name];
    }

    /**
     * @param string|File $resource Full file path or an instance of Devly\ConfigLoader\File object
     *
     * @return array<string, mixed>
     *
     * @throws FileNotFound if resource is string and is not a valid file path.
     * @throws FactoryError if resource type in not supported.
     * @throws ParseError if error occurs during parsing.
     */
    public function parse($resource): array
    {
        $resource = $resource instanceof File ? $resource : new File($resource);

        return $this->getParser($resource->extension())->parse($resource);
    }
}
