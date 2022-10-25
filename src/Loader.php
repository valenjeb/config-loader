<?php

declare(strict_types=1);

namespace Devly\ConfigLoader;

use Devly\ConfigLoader\Contracts\IStorage;
use Devly\ConfigLoader\Exceptions\DirectoryNotFound;
use Devly\ConfigLoader\Exceptions\FactoryError;
use Devly\ConfigLoader\Exceptions\FileNotFound;
use Devly\ConfigLoader\Exceptions\LoaderError;
use Devly\ConfigLoader\Exceptions\ParseError;
use Devly\ConfigLoader\Exceptions\StorageError;
use Devly\ConfigLoader\Storages\FileStorage;
use Devly\Repository;
use Throwable;

use function array_diff;
use function implode;
use function in_array;
use function is_dir;
use function is_string;
use function json_encode;
use function md5;
use function pathinfo;
use function rtrim;
use function scandir;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

class Loader
{
    /** @var array<string, mixed>  */
    protected array $items = [];
    protected bool $safeMode;
    protected bool $loadedFromCache = false;
    protected ParserFactory $parserFactory;
    protected ?IStorage $storage;

    /**
     * @param bool                 $safeMode If true, exceptions will not be thrown.
     * @param string|IStorage|null $storage
     *
     * @throws StorageError
     */
    public function __construct(bool $safeMode = false, $storage = null)
    {
        $this->safeMode      = $safeMode;
        $this->parserFactory = new ParserFactory();
        $this->setStorage($storage);
    }

    public function cacheEnabled(): bool
    {
        return $this->storage !== null;
    }

    public function isSafeMode(): bool
    {
        return $this->safeMode;
    }

    public function setSafeMode(bool $safeMode = true): self
    {
        $this->safeMode = $safeMode;

        return $this;
    }

    /**
     * @param string|string[]      $paths   A full file or directory path or an array of paths to load
     * @param bool                 $segment Whether to segment configurations by filename
     * @param string|string[]|null $formats File formats (Valid formats: php, json, ini, xml, neon, yaml)
     *
     * @throws FileNotFound
     * @throws StorageError
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function load($paths, bool $segment = false, $formats = null): Repository
    {
        $paths = (array) $paths;

        try {
            $cacheHandler = $this->getCacheHandler();
            $key          = md5(json_encode($paths));
            if ($cacheHandler->has($key)) {
                $this->loadedFromCache = true;

                return new Repository($cacheHandler->read($key));
            }
        } catch (LoaderError $e) {
        }

        $items = [];

        foreach ($paths as $path) {
            if (! is_string($path)) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->handleException(new LoaderError($path));

                continue;
            }

            try {
                $items += $this->loadFromDirectory($path, $formats, $segment);

                continue;
            } catch (FileNotFound | DirectoryNotFound $e) {
            } catch (LoaderError $e) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->handleException($e);

                continue;
            }

            $formats = (array) $formats;

            if (! empty($formats) && ! in_array(pathinfo($path, PATHINFO_EXTENSION), $formats)) {
                continue;
            }

            try {
                $items += $this->loadFromFile($path, $segment);
            } catch (Throwable $e) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $this->handleException($e);
            }
        }

        try {
            $cacheHandler = $this->getCacheHandler();
            $key          = md5(json_encode($paths));
            $cacheHandler->write($key, $items);
        } catch (LoaderError $e) {
        }

        $this->loadedFromCache = false;

        return new Repository($items);
    }

    /**
     * @param string|File $file    Full configuration file filepath or an instance of `Devly\ConfigLoader\File`
     * @param bool        $segment Whether to segment configurations by file name
     *
     * @return array<string, mixed>
     *
     * @throws FactoryError
     * @throws FileNotFound
     * @throws ParseError
     */
    protected function loadFromFile($file, bool $segment = false): array
    {
        $file = $file instanceof File ? $file : new File($file);

        try {
            $items = $this->getParserFactory()->parse($file);
        } catch (FactoryError | ParseError $e) {
            if (! $this->isSafeMode()) {
                throw $e;
            }

            return [];
        }

        if ($segment) {
            $items = [$file->name() => $items];
        }

        return $items;
    }

    /**
     * @param string|string[]|null $formats
     *
     * @return array<string|int, mixed>
     *
     * @throws DirectoryNotFound
     * @throws FileNotFound
     * @throws LoaderError
     */
    protected function loadFromDirectory(string $dirPath, $formats = null, bool $segment = false): array
    {
        if (! is_dir($dirPath)) {
            throw new DirectoryNotFound($dirPath);
        }

        $filePaths = array_diff(scandir($dirPath), ['.', '..']);

        $errors = [];

        $items = [];

        foreach ($filePaths as $filePath) {
            $file = new File(rtrim($dirPath) . DIRECTORY_SEPARATOR . $filePath);

            if (! empty($formats) && ! in_array($file->extension(), (array) $formats)) {
                continue;
            }

            try {
                $items += $this->loadFromFile($file, $segment);
            } catch (FactoryError | ParseError $e) {
                $errors[] = $filePath;
            }
        }

        if (! empty($errors) && ! $this->isSafeMode()) {
            throw new LoaderError(sprintf(
                'Some files failed to load because of an error: %s.',
                implode(', ', $errors)
            ));
        }

        return $items;
    }

    /** @throws Throwable */
    protected function handleException(Throwable $exception): void
    {
        if ($this->isSafeMode()) {
            return;
        }

        throw $exception;
    }

    protected function getParserFactory(): ParserFactory
    {
        return $this->parserFactory;
    }

    /**
     * Get instance of the cache handler object
     *
     * @throws LoaderError if cache handler is not enabled.
     */
    protected function getCacheHandler(): IStorage
    {
        if (! $this->cacheEnabled()) {
            throw new LoaderError(
                'Cache is not enabled. To enable cache, you should provide a full directory path' .
                ' or an instance of "Devly\ConfigLoader\Contracts\IStorage"'
            );
        }

        return $this->storage;
    }

    public function isLoadedFromCache(): bool
    {
        return $this->loadedFromCache;
    }

    /**
     * @param string|IStorage|null $storage
     *
     * @throws StorageError
     */
    public function setStorage($storage): self
    {
        $this->storage = $storage instanceof IStorage
            ? $storage
            : (is_string($storage) && ! empty($storage) ? new FileStorage($storage) : null);

        return $this;
    }
}
