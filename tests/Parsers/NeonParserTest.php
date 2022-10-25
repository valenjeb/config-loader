<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\NeonParser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class NeonParserTest extends TestCase
{
    protected NeonParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NeonParser();

        vfsStream::setup('root', null, [
            'config.neon' => 'foo: bar',
            'syntax-error.neon' => 'foo: bar, baz: foo',
        ]);
    }

    public function testParseNeonFile(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/config.neon'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testNeonSyntaxErrorThrowsParserException(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/syntax-error.neon'));
    }
}
