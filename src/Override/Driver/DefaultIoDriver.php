<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\Driver;


use Neunerlei\FileSystem\Fs;
use Neunerlei\FileSystem\Path;
use Neunerlei\Inflection\Inflector;
use Neunerlei\Lockpick\Override\Exception\FileNotFoundException;
use Neunerlei\Lockpick\Override\Exception\IncludeFileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;

class DefaultIoDriver implements IoDriverInterface
{
    protected const DRIVER_PATH = 'default';
    protected string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? sys_get_temp_dir();
    }

    /**
     * @inheritDoc
     */
    public function hasFile(string $filename): bool
    {
        return is_file($this->prepareFilename($filename));
    }

    /**
     * @inheritDoc
     */
    public function includeFile(string $filename, bool $once = true): mixed
    {
        if (!$this->hasFile($filename)) {
            throw new IncludeFileNotFoundException(
                sprintf(
                    'The file "%s" was not found in the storage directory "%s" (internal path would be "%s")',
                    $filename,
                    $this->storagePath,
                    $this->prepareFilename($filename)
                )
            );
        }

        return _lockpickDefaultIoDriverIncludeHelper(
            $this->prepareFilename($filename),
            $once
        );
    }

    /**
     * @inheritDoc
     */
    public function setFileContent(string $filename, string $content): bool
    {
        try {
            Fs::writeFile(
                $this->prepareFilename($filename),
                $content
            );
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFileContent(string $filename): string
    {
        try {
            return Fs::readFile($this->prepareFilename($filename));
        } catch (\Symfony\Component\Filesystem\Exception\FileNotFoundException|IOException) {
            throw new FileNotFoundException(sprintf('There is no file for name: "%s"', $filename));
        }
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        Fs::flushDirectory(
            Path::join($this->storagePath, static::DRIVER_PATH)
        );
    }

    /**
     * Prepares the storage filepath based on the given filename in the storage directory
     * @param string $filename
     * @return string
     */
    protected function prepareFilename(string $filename): string
    {
        $filename = Inflector::toFile($filename);
        return Path::join($this->storagePath, static::DRIVER_PATH, $filename);
    }
}

/**
 * External helper to make sure the file does not inherit the $this context
 *
 * @param string $file
 * @param bool $once
 *
 * @return mixed
 */
function _lockpickDefaultIoDriverIncludeHelper(string $file, bool $once): mixed
{
    if ($once) {
        /** @noinspection UsingInclusionOnceReturnValueInspection */
        return include_once $file;
    }

    return include $file;
}