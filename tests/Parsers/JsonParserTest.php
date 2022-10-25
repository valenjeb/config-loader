<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\JsonParser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class JsonParserTest extends TestCase
{
    protected JsonParser $parser;

    protected function setUp(): void
    {
        $this->parser = new JsonParser();

        vfsStream::setup('root', null, [
            'config.json' => '{"foo": "bar"}',
            'syntax-error.json' => '{"foo": "bar}',
        ]);
    }

    public function testParseJsonFile(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/config.json'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testJsonSyntaxErrorThrowsParserException(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/syntax-error.json'));
    }
}
