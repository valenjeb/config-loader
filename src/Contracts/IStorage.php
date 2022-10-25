<?php

declare(strict_types=1);

namespace Devly\ConfigLoader\Contracts;

use Devly\ConfigLoader\Exceptions\StorageError;

interface IStorage
{
    /**
     * Checks whether cached file exists
     */
    public function has(string $key): bool;

    /**
     * Read from cached configuration file
     *
     * @return array<string|int, mixed>
     *
     * @throws StorageError if no cache found for the provided key.
     */
    public function read(string $key): array;

    /**
     * Write configurations to a storage
     *
     * @param array<string|int, mixed> $content
     *
     * @throws StorageError if error occurs while writing to cache.
     */
    public function write(string $key, array $content): bool;

    /**
     * Delete cached configurations by its key name
     *
     * @return bool true on success or if file not exists. false on failure.
     *
     * @throws StorageError if failed to delete cached configurations.
     */
    public function drop(string $key): bool;
}
