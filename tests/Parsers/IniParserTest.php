<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\IniParser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class IniParserTest extends TestCase
{
    protected IniParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IniParser();

        vfsStream::setup('root', null, [
            'config.ini' => 'foo = bar',
            'syntax-error.ini' => 'foo == bar',
        ]);
    }

    public function testParseIniFile(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/config.ini'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testIniSyntaxErrorThrowsParserException(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/syntax-error.ini'));
    }
}
