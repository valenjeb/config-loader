<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Parsers;

use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Parsers\PhpParser;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class PhpParserTest extends TestCase
{
    protected PhpParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PhpParser();

        vfsStream::setup('root', null, [
            'config.php' => '<?php return ["foo" => "bar"];',
            'callback.php' => '<?php return fn () => ["foo" => "bar"];',
            'fails.php' => '<?php echo "";',
        ]);
    }

    public function testParseFileReturnsAnArray(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/config.php'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testParseFileReturnsCallback(): void
    {
        $output = $this->parser->parse(vfsStream::url('root/callback.php'));

        $this->assertEquals(['foo' => 'bar'], $output);
    }

    public function testParserThrowsParserErrorIfNotReturnsAnArray(): void
    {
        $this->expectException(ParseError::class);
        $this->expectExceptionMessage('Failed parsing "vfs://root/config.php": PHP file must return an array.');

        vfsStream::setup('root', null, ['config.php' => '<?php echo "";']);

        $this->parser->parse(vfsStream::url('root/config.php'));
    }
}
