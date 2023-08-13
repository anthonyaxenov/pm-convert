<?php

declare(strict_types=1);

namespace PmConverter;

use PmConverter\Exceptions\{
    CannotCreateDirectoryException,
    DirectoryIsNotReadableException,
    DirectoryIsNotWriteableException,
    DirectoryNotExistsException};

/**
 * Helper class to work with files and directories
 */
class FileSystem
{
    /**
     * Normalizes a given path
     *
     * @param string $path
     * @return string
     */
    public static function normalizePath(string $path): string
    {
        $path = str_replace('~', $_SERVER['HOME'], $path);
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Recursively creates a new directory by given path
     *
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
     * Recursively removes a given directory
     *
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
     * Returns content of given directory path
     *
     * @param string $path
     * @return array
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

    /**
     * Checks if a given file is a valid collection json file
     *
     * @param string $path
     * @return bool
     */
    public static function isCollectionFile(string $path): bool
    {
        $path = static::normalizePath($path);
        return !empty($path = trim($path))
            && str_ends_with($path, '.postman_collection.json')
            && file_exists($path)
            && is_readable($path)
            && ($json = json_decode(file_get_contents($path), true))
            && json_last_error() === JSON_ERROR_NONE
            && isset($json['collection']['info']['name']);
    }
}
