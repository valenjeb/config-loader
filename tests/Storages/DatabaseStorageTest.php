<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Tests\Storages;

use Devly\ConfigLoader\Exceptions\StorageError;
use Devly\ConfigLoader\Loader;
use Devly\ConfigLoader\Storages\DatabaseStorage;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

use function json_encode;
use function md5;

class DatabaseStorageTest extends TestCase
{
    protected DatabaseStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new DatabaseStorage('config_loader', 'root', 'root', '127.0.0.1');
    }

    public function testWriteReadHasAndDrop(): void
    {
        $data = ['foo' => 'bar', 'bar' => 'baz'];
        $key  = md5('custom_key');

        $this->storage->write($key, $data);

        $this->assertTrue($this->storage->has($key));

        $this->assertEquals($data, $this->storage->read($key));
        $this->assertTrue($this->storage->drop($key));
    }

    public function testReadThrowsStorageErrorIfKeyNotFound(): void
    {
        $this->expectException(StorageError::class);

        $this->storage->read(md5('fake'));
    }

    public function testUseWithLoader(): void
    {
        vfsStream::setup('root', null, ['app.json' => '{"name": "ACME"}']);

        $loader = new Loader(false, $this->storage);

        $loader->load(vfsStream::url('root'), true);
        $this->assertFalse($loader->isLoadedFromCache());

        $loader->load(vfsStream::url('root'), true);
        $this->assertTrue($loader->isLoadedFromCache());

        $key = md5(json_encode((array) vfsStream::url('root')));
        $this->storage->drop($key);
    }
}
