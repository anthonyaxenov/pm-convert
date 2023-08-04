<?php

declare(strict_types = 1);

namespace PmConverter;

use PmConverter\Exceptions\{
    CannotCreateDirectoryException,
    DirectoryIsNotReadableException,
    DirectoryIsNotWriteableException,
    DirectoryNotExistsException};

class FileSystem
{
    public static function normalizePath(string $path): string
    {
        $path = str_replace('~', $_SERVER['HOME'], $path);
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $path
     * @return string
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     */
    public static function makeDir(string $path): string
    {
        $path = static::normalizePath($path);
        if (!file_exists($path)) {
            mkdir($path, recursive: true)
                || throw new CannotCreateDirectoryException("cannot create output directory: $path");
        }
        if (!is_writable($path)) {
            throw new DirectoryIsNotWriteableException("output directory permissions are not valid: $path");
        }
        return $path;
    }

    /**
     * @param string $path
     * @return void
     * @throws DirectoryIsNotReadableException
     * @throws DirectoryIsNotWriteableException
     * @throws DirectoryNotExistsException
     */
    public static function removeDir(string $path): void
    {
        $path = static::normalizePath($path);
        $dir_contents = static::dirContents($path);
        foreach ($dir_contents as $record) {
            is_dir($record) ? static::removeDir($record) : @unlink($record);
        }
        file_exists($path) && @rmdir($path);
    }

    /**
     * @param string $path
     * @return bool
     * @throws DirectoryIsNotWriteableException
     * @throws DirectoryNotExistsException
     * @throws DirectoryIsNotReadableException
     */
    public static function checkDir(string $path): bool
    {
        $path = static::normalizePath($path);
        if (!file_exists($path)) {
            throw new DirectoryNotExistsException("directory does not exist: $path");
        }
        if (!is_readable($path)) {
            throw new DirectoryIsNotReadableException("directory permissions are not valid: $path");
        }
        if (!is_writable($path)) {
            throw new DirectoryIsNotWriteableException("directory permissions are not valid: $path");
        }
        return true;
    }

    /**
     * @param string $path
     * @return array
     * @throws DirectoryIsNotReadableException
     * @throws DirectoryIsNotWriteableException
     * @throws DirectoryNotExistsException
     */
    public static function dirContents(string $path): array
    {
        $path = static::normalizePath($path);
        $records = array_diff(@scandir($path) ?: [], ['.', '..']);
        foreach ($records as &$record) {
            $record = sprintf('%s%s%s', $path, DIRECTORY_SEPARATOR, $record);
        }
        return $records;
    }
}
