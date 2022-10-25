<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\YamlParser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class YamlParserTest extends TestCase
{
    protected YamlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new YamlParser();

        vfsStream::setup('root', null, [
            'config.yaml' => '{foo: bar}',
            'syntax-error.yaml' => 'foo: some string with a colon: in the middle',
            'return-string.yaml' => 'foo',
        ]);
    }

    public function testParseYamlFile(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/config.yaml'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testYamlSyntaxErrorThrowsParserException(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/syntax-error.yaml'));
    }

    public function testParsedYamlFileDoesNotParsedAsArray(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/return-string.yaml'));
    }
}
