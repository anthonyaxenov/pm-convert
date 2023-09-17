<?php

declare(strict_types = 1);

namespace PmConverter;

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
    /**
     * Closed constructor so that we could use factory methods
     *
     * @param object $json
     */
    private function __construct(protected object $json)
    {
        // specific case when collection has been exported via postman api
        if (isset($json->collection)) {
            $json = $json->collection;
        }
        $this->json = $json;
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
    public function version(): CollectionVersion
    {
        return match (true) {
            str_contains($this->json->info->schema, '/v2.0.') => CollectionVersion::Version20,
            str_contains($this->json->info->schema, '/v2.1.') => CollectionVersion::Version21,
            default => CollectionVersion::Unknown
        };
    }
}
