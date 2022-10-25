<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Storages;

use Devly\ConfigLoader\Contracts\IStorage;
use Devly\ConfigLoader\Exceptions\StorageError;

use function error_get_last;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function is_dir;
use function mkdir;
use function realpath;
use function sprintf;
use function unlink;
use function var_export;

class FileStorage implements IStorage
{
    protected string $cacheDirPath;

    /** @throws StorageError if the cache dir not exists at provided path and could not be created automatically. */
    public function __construct(string $cacheDirPath)
    {
        if (! is_dir($cacheDirPath) && ! mkdir($cacheDirPath, 0775, true)) {
            throw new StorageError(sprintf('Path "%s" must be present.', $cacheDirPath));
        }

        $this->cacheDirPath = realpath($cacheDirPath);
    }

    public function has(string $key): bool
    {
        return file_exists($this->parseFileName($key));
    }

    /** @inheritDoc */
    public function read(string $key): array
    {
        if (! $this->has($key)) {
            throw new StorageError(sprintf('No cache found for key "%s"', $key));
        }

        return require $this->parseFileName($key);
    }

    /** @inheritDoc */
    public function write(string $key, array $content): bool
    {
        $fname = $this->parseFileName($key);

        $resource = fopen($fname, 'w');

        if ($resource === false) {
            $lastError = error_get_last();

            throw new StorageError($lastError['message']);
        }

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $content = sprintf("<?php\n\n/** This file generated automatically. any changes to this file will be lost. */\n\nreturn %s;\n", var_export($content, true));

        $result = fwrite($resource, $content);

        if ($result === false) {
            $lastError = error_get_last();

            throw new StorageError($lastError['message']);
        }

        fclose($resource);

        return true;
    }

    public function drop(string $key): bool
    {
        if (! unlink($this->parseFileName($key))) {
            throw new StorageError(sprintf(
                'Failed to delete "%s" file cache: "%s"',
                $key,
                error_get_last()['message']
            ));
        }

        return true;
    }

    protected function parseFileName(string $key): string
    {
        return sprintf(
            '%s/config.%s.php',
            $this->cacheDirPath,
            $key
        );
    }
}
