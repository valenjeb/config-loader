<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests;

use Devly\ConfigLoader\Exceptions\FileNotFound;
use Devly\ConfigLoader\File;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup('config');
    }

    public function testCreateInstance(): void
    {
        vfsStream::create(['config.php' => '']);

        $file = new File(vfsStream::url('config/config.php'));

        $this->assertInstanceOf(File::class, $file);
    }

    public function testCreateInstanceThrowsFileNotFoundException(): void
    {
        $this->expectException(FileNotFound::class);

        new File(vfsStream::url('not_found.php'));
    }

    public function testGetFilePath(): void
    {
        vfsStream::create(['config.php' => '']);

        $file = new File(vfsStream::url('config/config.php'));

        $this->assertEquals('vfs://config/config.php', $file->path());
    }

    public function testGetFileExtension(): void
    {
        vfsStream::create([
            'config.php' => '',
            'config.dist.php' => '',
        ]);

        $file = new File(vfsStream::url('config/config.php'));

        $this->assertEquals('php', $file->extension());

        $file = new File(vfsStream::url('config/config.dist.php'));

        $this->assertEquals('php', $file->extension());
    }

    public function testGetFileExtensionFromSuffixedFilePath(): void
    {
        vfsStream::create([
            'config.php.dist' => '',
            'config.php.dev' => '',
            'config.php.production' => '',
            'config.php.local' => '',
        ]);

        $dist = new File(vfsStream::url('config.php.dist'));

        $this->assertEquals('php', $dist->extension());

        $dev = new File(vfsStream::url('config.php.dev'));

        $this->assertEquals('php', $dev->extension());

        $production = new File(vfsStream::url('config.php.production'));

        $this->assertEquals('php', $production->extension());

        $local = new File(vfsStream::url('config.php.local'));

        $this->assertEquals('php', $local->extension());
    }

    public function testGetFileNameWithoutExtension(): void
    {
        vfsStream::create([
            'config.php.dist' => '',
            'config.dist.php' => '',
        ]);

        $file = new File(vfsStream::url('config/config.php.dist'));

        $this->assertEquals('config', $file->name());

        $file = new File(vfsStream::url('config/config.dist.php'));

        $this->assertEquals('config.dist', $file->name());
    }

    public function testGetBaseName(): void
    {
        vfsStream::create(['config.php.dist' => '']);

        $file = new File(vfsStream::url('config/config.php.dist'));

        $this->assertEquals('config.php.dist', $file->basename());
    }

    public function testGetFileContents(): void
    {
        vfsStream::create(['config.json' => '{"foo": "bar"}']);

        $file = new File(vfsStream::url('config/config.json'));

        $this->assertEquals('{"foo": "bar"}', $file->contents());
    }

    public function testGetFileSize(): void
    {
        vfsStream::create(['config.php' => 'foo']);

        $file = new File(vfsStream::url('config/config.php'));

        $this->assertEquals(3, $file->size());
    }

    public function testCheckWhetherIsEmpty(): void
    {
        vfsStream::create([
            'config.php' => 'foo',
            'empty.php' => '',
        ]);

        $file = new File(vfsStream::url('config/config.php'));

        $this->assertFalse($file->isEmpty());

        $file = new File(vfsStream::url('config/empty.php'));
        $this->assertTrue($file->isEmpty());
    }
}
