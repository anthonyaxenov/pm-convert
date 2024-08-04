<?php

declare(strict_types=1);

namespace PmConverter;

use ArrayAccess;
use JsonException;

/**
 *
 */
class Environment implements ArrayAccess
{
    /**
     * @var string Path to env file
     */
    protected static string $filepath = '';

    /**
     * @var Environment
     */
    protected static Environment $instance;

    /**
     * @var array
     */
    protected array $ownVars = [];

    /**
     * @var array
     */
    protected array $customVars = [];

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    /**
     * @param string|null $filepath
     * @return $this
     * @throws JsonException
     */
    public function readFromFile(?string $filepath): static
    {
        if (empty($filepath)) {
            return $this;
        }
        $content = file_get_contents(static::$filepath = $filepath);
        $content = json_decode($content, flags: JSON_THROW_ON_ERROR); //TODO try-catch
        $content || throw new JsonException("not a valid environment: $filepath");
        property_exists($content, 'environment') && $content = $content->environment;
        if (!property_exists($content, 'id') && !property_exists($content, 'name')) {
            throw new JsonException("not a valid environment: $filepath");
        }
        return $this->setOwnVars($content->values);
    }

    /**
     * @param array $vars
     * @return $this
     */
    protected function setOwnVars(array $vars): static
    {
        foreach ($vars as $key => $value) {
            is_object($value) && [$key, $value] = [$value->key, $value->value];
            $this->setOwnVar($key, $value);
        }
        return $this;
    }

    /**
     * Sets value to some environment own variable
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    protected function setOwnVar(string $name, string $value): static
    {
        $this->ownVars[static::formatKey($name)] = $value;
        return $this;
    }

    /**
     * @param array $vars
     * @return $this
     */
    public function setCustomVars(array $vars): static
    {
        foreach ($vars as $key => $value) {
            is_object($value) && [$key, $value] = [$value->key, $value->value];
            $this->setCustomVar($key, $value);
        }
        return $this;
    }

    /**
     * Sets value to some environment own variable
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setCustomVar(string $name, string $value): static
    {
        $this->customVars[static::formatKey($name)] = $value;
        return $this;
    }

    /**
     * Returns value of specific variable
     *
     * @param string $name
     * @return mixed
     */
    public function var(string $name): mixed
    {
        $format_key = static::formatKey($name);
        return $this->ownVars[$format_key] ?? $this->customVars[$format_key] ?? null;
    }

    /**
     * Returns array of own and custom variables
     *
     * @return string[]
     */
    public function vars(): array
    {
        return array_merge($this->ownVars, $this->customVars);
    }

    /**
     * Returns array of custom variables
     *
     * @return string[]
     */
    public function customVars(): array
    {
        return $this->customVars;
    }

    /**
     * Tells if there are some vars or not
     *
     * @return bool
     */
    public function hasVars(): bool
    {
        return !empty($this->ownVars) && !empty($this->customVars);
    }













    /**
     * Closed constructor
     */
    protected function __construct()
    {
    }


    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists(static::formatKey($offset), $this->vars());
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->var($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->customVars[static::formatKey($offset)] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->customVars[static::formatKey($offset)]);
    }

    /**
     * Returns correct variable {{name}}
     *
     * @param string $key
     * @return string
     */
    public static function formatKey(string $key): string
    {
        return sprintf('{{%s}}', str_replace(['{', '}'], '', $key));
    }
}
