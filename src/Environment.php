<?php

declare(strict_types=1);

namespace PmConverter;

class Environment implements \ArrayAccess
{
    /**
     * @var array
     */
    protected array $vars = [];

    /**
     * @param object|null $env
     */
    public function __construct(protected ?object $env)
    {
        if (!empty($env->values)) {
            foreach ($env->values as $var) {
                $this->vars[static::formatKey($var->key)] = $var->value;
            }
        }
    }

    /**
     * Tells if there are some vars or not
     *
     * @return bool
     */
    public function hasVars(): bool
    {
        return !empty($this->vars);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists(static::formatKey($offset), $this->vars);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->vars[static::formatKey($offset)] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->vars[static::formatKey($offset)] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->vars[static::formatKey($offset)]);
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
