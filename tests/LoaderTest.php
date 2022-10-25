<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests;

use Devly\ConfigLoader\Exceptions\FileNotFound;
use Devly\ConfigLoader\Exceptions\LoaderError;
use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Loader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    protected Loader $loader;

    protected function setUp(): void
    {
        $this->loader = new Loader();
    }

    protected function tearDown(): void
    {
        unset($this->loader);
    }

    public function testCreateLoaderInSafeMode(): void
    {
        $loader = new Loader(true);

        $this->assertTrue($loader->isSafeMode());
    }

    public function testSetSafeMode(): void
    {
        $this->loader->setSafeMode();

        $this->assertTrue($this->loader->isSafeMode());

        $this->loader->setSafeMode(false);

        $this->assertFalse($this->loader->isSafeMode());
    }

    public function testLoadConfigFromFile(): void
    {
        vfsStream::setup('root', null, ['app.php' => '<?php return ["name" => "ACME"];']);

        $repository = $this->loader->load(vfsStream::url('root/app.php'));

        $this->assertEquals('ACME', $repository->get('name'));
    }

    public function testLoadConfigFromFileWithSegmentEnabled(): void
    {
        vfsStream::setup('root', null, ['app.php' => '<?php return ["name" => "ACME"];']);

        $repo = $this->loader->load(vfsStream::url('root/app.php'), true);

        $this->assertEquals('ACME', $repo->get('app.name'));
    }

    public function testLoadConfigFromFileThrowsFileNotFoundException(): void
    {
        $this->expectException(FileNotFound::class);

        $this->loader->load('path/to/app.php');
    }

    public function testLoadConfigFromFileThrowsParseError(): void
    {
        $this->expectException(ParseError::class);

        vfsStream::setup('root', null, ['app.json' => '{"name": "ACME}']);

        $this->loader->load(vfsStream::url('root/app.json'));
    }

    public function testLoadConfigFromFileSkipsError(): void
    {
        vfsStream::setup('root', null, ['app.json' => '{"name": "ACME}']);

        $this->loader->setSafeMode();
        $repository = $this->loader->load(vfsStream::url('root/app.json'));

        $this->assertEmpty($repository->all());
    }

    public function testLoadConfigFromDirectory(): void
    {
        vfsStream::setup('root', null, [
            'app.php' => '<?php return ["name" => "ACME"];',
            'db.php' => '<?php return ["host" => "localhost"];',
        ]);

        $repository = $this->loader->load(vfsStream::url('root'));

        $this->assertEquals(['name' => 'ACME', 'host' => 'localhost'], $repository->all());
    }

    public function testLoadConfigFromDirectoryWithSegmentEnabled(): void
    {
        vfsStream::setup('root', null, [
            'app.php' => '<?php return ["name" => "ACME"];',
            'db.php' => '<?php return ["host" => "localhost"];',
        ]);

        $repository = $this->loader->load(vfsStream::url('root'), true);

        $this->assertEquals([
            'app' => ['name' => 'ACME'],
            'db' => ['host' => 'localhost'],
        ], $repository->all());
    }

    public function testLoadConfigFromDirectoryThrowsLoaderError(): void
    {
        $this->expectException(LoaderError::class);

        vfsStream::setup('root', null, ['app.json' => '{"name": "ACME}']);

        $this->loader->load(vfsStream::url('root'));
    }

    public function testLoadConfigFromDirectorySkipsErrors(): void
    {
        vfsStream::setup('root', null, [
            'app.json' => '{"name": "ACME}', // syntax error should be skipped
            'db.json' => '{"name": "db_name", "host": "localhost"}',
        ]);

        $this->loader->setSafeMode();
        /** @noinspection PhpUnhandledExceptionInspection */
        $repository = $this->loader->load(vfsStream::url('root'), true);

        $this->assertEquals(['db' => ['name' => 'db_name', 'host' => 'localhost']], $repository->all());
    }

    public function testLoadConfigFromDirectoryFilteredByType(): void
    {
        vfsStream::setup('root', null, [
            'app.json' => '{"name": "ACME"}',
            'db.php' => '<?php return ["name" => "db_name", "host" => "localhost"];',
        ]);

        $repository = $this->loader->load(vfsStream::url('root'), true, 'php');

        $this->assertEquals(['db' => ['name' => 'db_name', 'host' => 'localhost']], $repository->all());
    }

    public function testEnableCacheWithDirPath(): void
    {
        $this->loader->setStorage(__DIR__ . '/tmp');

        $this->assertTrue($this->loader->cacheEnabled());
    }
}
