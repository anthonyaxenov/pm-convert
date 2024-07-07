<?php

declare(strict_types = 1);

namespace PmConverter;

use Exception;
use Generator;
use JsonException;
use Stringable;

/**
 * Class that describes a request collection
 *
 * @property array|object $item
 * @property object $info
 * @property object|null $variable
 */
class Collection implements Stringable
{
    public readonly CollectionVersion $version;

    /**
     * Closed constructor so that we could use factory methods
     *
     * @param object $json
     */
    private function __construct(protected object $json)
    {
        // specific case when collection has been exported via postman api
        if (property_exists($json, 'collection')) {
            $json = $json->collection;
        }
        $this->json = $json;
        $this->version = $this->detectVersion();
    }

    /**
     * Factory that creates new Collection from content read from file path
     *
     * @param string $path
     * @return static
     * @throws JsonException
     */
    public static function fromFile(string $path): static
    {
        $content = file_get_contents(FileSystem::normalizePath($path));
        $json = json_decode($content, flags: JSON_THROW_ON_ERROR);
        return new static($json);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return json_encode($this->json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Returns reference to the parsed json structure
     *
     * @return object
     */
    public function &raw(): object
    {
        return $this->json;
    }

    /**
     * Returns reference to any part of the parsed json structure
     *
     * @param string $name
     * @return mixed
     */
    public function &__get(string $name): mixed
    {
        return $this->json->$name;
    }

    /**
     * Returns collection name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->json->info->name;
    }

    /**
     * Returns the collection version
     *
     * @return CollectionVersion
     */
    protected function detectVersion(): CollectionVersion
    {
        return match (true) {
            str_contains($this->json->info->schema, '/v2.0.') => CollectionVersion::Version20,
            str_contains($this->json->info->schema, '/v2.1.') => CollectionVersion::Version21,
            default => CollectionVersion::Unknown
        };
    }

    /**
     * Returns the collection version from raw file
     *
     * @param string $filepath
     * @return CollectionVersion
     * @throws Exception
     */
    public static function detectFileVersion(string $filepath): CollectionVersion
    {
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new Exception("Cannot open file for reading: $filepath");
        }
        $content = '';
        // Postman collection files may be HUGE and I don't need to parse
        // them here to find value .info.schema field because normally it
        // is stored at the beginning of a file, so if it's not then this
        // is a user problem, not mine.
        while (\mb_strlen($content) <= 2048) {
            $content .= fgets($handle, 50);
            if (str_contains($content, 'https://schema.getpostman.com/json/collection')) {
                if (str_contains($content, '/v2.0.')) {
                    return CollectionVersion::Version20;
                }
                if (str_contains($content, '/v2.1.')) {
                    return CollectionVersion::Version21;
                }
            }
        }
        return CollectionVersion::Unknown;
    }

    /**
     * Iterates over collection request items and returns item associated by its path in folder
     *
     * @param mixed|null $item
     * @return Generator
     */
    public function iterate(mixed $item = null): Generator
    {
        $is_recursive = !is_null($item);
        $folder = $is_recursive ? $item : $this->json;
        static $dir_tree;
        $path = DS . ($is_recursive ? implode(DS, $dir_tree ?? []) : '');
        foreach ($folder->item as $subitem) {
            if ($this->isItemFolder($subitem)) {
                $dir_tree[] = $subitem->name;
                yield from $this->iterate($subitem);
                continue;
            }
            yield $path => $subitem;
        }
        $is_recursive && array_pop($dir_tree);
    }

    /**
     * Checks whether item contains another items or not
     *
     * @param object $item
     * @return bool
     */
    protected function isItemFolder(object $item): bool
    {
        return !empty($item->item)
            && is_array($item->item)
            && empty($item->request);
    }
}
