<?php

declare(strict_types=1);

namespace PmConverter\Converters\Abstract;

use Exception;
use Iterator;
use PmConverter\Collection;
use PmConverter\Converters\RequestContract;
use PmConverter\Environment;
use PmConverter\Exceptions\CannotCreateDirectoryException;
use PmConverter\Exceptions\DirectoryIsNotWriteableException;
use PmConverter\Exceptions\InvalidHttpVersionException;
use PmConverter\FileSystem;

/**
 *
 */
abstract class AbstractConverter
{
    /**
     * @var Collection|null
     */
    protected ?Collection $collection = null;

    /**
     * @var string
     */
    protected string $outputPath;

    /**
     * @var RequestContract[] Converted requests
     */
    protected array $requests = [];

    /**
     * Sets output path
     *
     * @param string $outputPath
     * @return $this
     */
    public function to(string $outputPath): self
    {
        $this->outputPath = sprintf('%s%s%s', $outputPath, DS, static::OUTPUT_DIR);
        return $this;
    }

    /**
     * Converts requests from collection
     *
     * @param Collection $collection
     * @return static
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     * @throws Exception
     */
    public function convert(Collection $collection): static
    {
        $this->collection = $collection;
        $this->outputPath = FileSystem::makeDir($this->outputPath);
        $this->setCollectionVars();
        foreach ($collection->iterate() as $path => $item) {
            // $this->requests[$path][] = $this->makeRequest($item);
            $this->writeRequest($this->makeRequest($item), $path);
        }
        return $this;
    }

    /**
     * Returns converted requests
     *
     * @return Iterator<string, RequestContract>
     */
    public function converted(): Iterator
    {
        foreach ($this->requests as $path => $requests) {
            foreach ($requests as $request) {
                yield $path => $request;
            }
        }
    }

    /**
     * Writes requests on disk
     *
     * @throws Exception
     */
    public function flush(): void
    {
        foreach ($this->converted() as $path => $request) {
            $this->writeRequest($request, $path);
        }
    }

    /**
     * Prepares collection variables
     *
     * @return $this
     */
    protected function setCollectionVars(): static
    {
        foreach ($this->collection?->variable ?? [] as $var) {
            Environment::instance()->setCustomVar($var->key, $var->value);
        }
        return $this;
    }

    /**
     * Returns output path
     *
     * @return string
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
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

    /**
     * Initialiazes request object to be written in file
     *
     * @param object $item
     * @return RequestContract
     * @throws InvalidHttpVersionException
     */
    protected function makeRequest(object $item): RequestContract
    {
        $request_class = static::REQUEST_CLASS;

        /** @var RequestContract $request */
        $request = new $request_class();
        $request->setName($item->name);
        $request->setVersion($this->collection->version);
        $request->setHttpVersion(1.1); //TODO http version?
        $request->setDescription($item->request?->description ?? null);
        $request->setVerb($item->request->method);
        $request->setUrl($item->request->url);
        $request->setHeaders($item->request->header);
        $request->setAuth($item->request?->auth ?? $this->collection?->auth ?? null);
        if ($item->request->method !== 'GET' && !empty($item->request->body)) {
            $request->setBody($item->request->body);
        }
        return $request;
    }

    /**
     * Writes converted request object to file
     *
     * @param RequestContract $request
     * @param string|null $subpath
     * @return bool
     * @throws Exception
     */
    protected function writeRequest(RequestContract $request, string $subpath = null): bool
    {
        $filedir = sprintf('%s%s%s', $this->outputPath, DS, $subpath);
        $filedir = FileSystem::makeDir($filedir);
        $filepath = sprintf('%s%s%s.%s', $filedir, DS, $request->getName(), static::FILE_EXT);
        $content = $this->interpolate((string)$request);
        return file_put_contents($filepath, $content) > 0;
    }

    /**
     * Replaces variables in request with values from collection or environment
     *
     * @param string $content
     * @return string
     */
    protected function interpolate(string $content): string
    {
        $replace = static fn ($a) => Environment::instance()->var($var = $a[0]) ?: $var;
        return Environment::instance()->hasVars()
            ? preg_replace_callback('/\{\{.*}}/m', $replace, $content)
            : $content;
    }
}
