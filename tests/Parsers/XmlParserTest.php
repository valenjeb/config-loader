<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\XmlParser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class XmlParserTest extends TestCase
{
    protected XmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new XmlParser();

        vfsStream::setup('root', null, [
            'config.xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<config><foo>bar</foo></config>",
            'syntax-error.xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<config><foo>bar<foo></config>",
        ]);
    }

    public function testParseXmlFile(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/config.xml'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testXmlSyntaxErrorThrowsParserException(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/syntax-error.xml'));
    }
}
