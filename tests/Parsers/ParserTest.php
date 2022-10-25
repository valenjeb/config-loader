<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\FileNotFound;
use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\File;
use Devly\ConfigLoader\Parsers\Parser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    protected Parser $parser;

    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['config.json' => '']);

        $this->parser = new class extends Parser {
            protected function parseFile(File $file): array // phpcs:ignore
            {
                return [];
            }
        };
    }

    public function testParserThrowsFileNotFoundException(): void
    {
        $this->expectException(FileNotFound::class);

        $this->parser->parse('fake.json');
    }

    public function testParserThrowsParserExceptionIfFileIsEmpty(): void
    {
        $this->expectException(ParseError::class);

        $this->parser->parse(vfsStream::url('root/config.json'));
    }
}
