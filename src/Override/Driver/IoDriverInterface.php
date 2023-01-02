<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\Driver;


use Neunerlei\Lockpick\Override\Exception\FileNotFoundException;
use Neunerlei\Lockpick\Override\Exception\IncludeFileNotFoundException;

interface IoDriverInterface
{
    /**
     * Returns true if a file exists, false if not
     *
     * @param string $filename The name / relative path of the file to check
     *
     * @return bool
     */
    public function hasFile(string $filename): bool;

    /**
     * Includes a file as a PHP resource
     *
     * @param string $filename The name of the file to include
     * @param bool $once by default, we include the file with include_once, if you set this to FALSE the plain
     *                             include is used instead.
     *
     * @return mixed
     * @throws IncludeFileNotFoundException
     */
    public function includeFile(string $filename, bool $once = true): mixed;

    /**
     * Is used to dump some content into a file.
     * Automatically serializes non-string/numeric content before writing it as a file
     *
     * @param string $filename The name / relative path of the file to dump the content to
     * @param string $content A string to be dumped into the file
     */
    public function setFileContent(string $filename, string $content): bool;

    /**
     * Must return the content of a file with the provided name
     * @param string $filename The name / relative path of the file to read the content for
     * @return string
     * @throws FileNotFoundException
     */
    public function getFileContent(string $filename): string;

    /**
     * Remove all files that can be accessed by this driver
     * @return void
     */
    public function flush(): void;
}