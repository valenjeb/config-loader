<?php

declare(strict_types=1);

namespace Devly\ConfigLoader;

use Devly\ConfigLoader\Exceptions\FileNotFound;

use function file_get_contents;
use function filesize;
use function in_array;
use function is_file;
use function pathinfo;
use function preg_match;
use function realpath;
use function strlen;
use function substr;

use const PATHINFO_BASENAME;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

class File
{
    protected string $filepath;
    protected string $extension;
    protected string $name;
    protected string $basename;
    protected string $contents;
    protected int $filesize;

    /** @throws FileNotFound if provided file does not exist or is not a file. */
    public function __construct(string $resource)
    {
        $filepath = realpath($resource) ?: (is_file($resource) ? $resource : false);

        if (! $filepath) {
            throw new FileNotFound($resource);
        }

        $this->filepath = $filepath;
    }

    /**
     * Retrieves the file extension.
     *
     * This method will remove production, local, dev, development and dist appendix to guess the real file extension.
     */
    public function extension(): string
    {
        if (isset($this->extension)) {
            return $this->extension;
        }

        $extension = pathinfo($this->path(), PATHINFO_EXTENSION);

        if (in_array($extension, ['production', 'local', 'dev', 'development', 'dist'])) {
            $path      = substr($this->path(), 0, -(strlen($extension) + 1));
            $extension = pathinfo($path, PATHINFO_EXTENSION);
        }

        return $this->extension = $extension;
    }

    /**
     * Retrieves full file path.
     */
    public function path(): string
    {
        return $this->filepath;
    }

    /**
     * Retrieves the file name without file extension.
     */
    public function name(): string
    {
        if (! isset($this->name)) {
            $name = pathinfo($this->path(), PATHINFO_FILENAME);
            if (preg_match('/(\.php|\.json|\.neon|\.xml|\.ini)$/i', $name)) {
                $name = pathinfo($name, PATHINFO_FILENAME);
            }

            $this->name = $name;
        }

        return $this->name;
    }

    /**
     * Retrieves the file name including file extension.
     */
    public function basename(): string
    {
        return $this->basename ?? $this->basename = pathinfo($this->path(), PATHINFO_BASENAME);
    }

    /**
     * Reads entire file into a string.
     */
    public function contents(): string
    {
        if (! isset($this->contents)) {
            $contents       = file_get_contents($this->path());
            $this->contents = $contents !== false ? $contents : '';
        }

        return $this->contents;
    }

    /**
     * Determine whether the file is empty.
     */
    public function isEmpty(): bool
    {
        return $this->size() === 0;
    }

    /**
     * Retrieves the file size in bytes.
     */
    public function size(bool $refresh = false): int
    {
        if ($refresh || ! isset($this->filesize)) {
            $this->filesize = filesize($this->path());
        }

        return $this->filesize;
    }
}
