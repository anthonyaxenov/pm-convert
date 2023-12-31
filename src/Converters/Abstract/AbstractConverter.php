<?php

declare(strict_types=1);

namespace PmConverter\Converters\Abstract;

use Exception;
use PmConverter\Collection;
use PmConverter\Converters\{
    ConverterContract,
    RequestContract};
use PmConverter\Environment;
use PmConverter\Exceptions\{
    CannotCreateDirectoryException,
    DirectoryIsNotWriteableException,
    InvalidHttpVersionException};
use PmConverter\FileSystem;

/**
 *
 */
abstract class AbstractConverter implements ConverterContract
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
     * @var Environment|null
     */
    protected ?Environment $env = null;

    /**
     * Sets an environment with vars
     *
     * @param Environment $env
     * @return $this
     */
    public function withEnv(Environment $env): static
    {
        $this->env = $env;
        return $this;
    }

    /**
     * Creates a new directory to save a converted collection into
     *
     * @param string $outputPath
     * @return void
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     */
    protected function prepareOutputDir(string $outputPath): void
    {
        $outputPath = sprintf('%s%s%s', $outputPath, DIRECTORY_SEPARATOR, static::OUTPUT_DIR);
        $this->outputPath = FileSystem::makeDir($outputPath);
    }

    /**
     * Converts collection requests
     *
     * @param Collection $collection
     * @param string $outputPath
     * @return void
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     * @throws Exception
     */
    public function convert(Collection $collection, string $outputPath): void
    {
        $this->prepareOutputDir($outputPath);
        $this->collection = $collection;
        $this->setVariables();
        foreach ($collection->item as $item) {
            $this->convertItem($item);
        }
    }

    /**
     * Prepares collection variables
     *
     * @return $this
     */
    protected function setVariables(): static
    {
        empty($this->env) && $this->env = new Environment($this->collection?->variable);
        if (!empty($this->collection?->variable)) {
            foreach ($this->collection->variable as $var) {
                $this->env[$var->key] = $var->value;
            }
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
     * Converts an item to request object and writes it into file
     *
     * @throws Exception
     */
    protected function convertItem(mixed $item): void
    {
        if ($this->isItemFolder($item)) {
            static $dir_tree;
            foreach ($item->item as $subitem) {
                $dir_tree[] = $item->name;
                $path = implode(DIRECTORY_SEPARATOR, $dir_tree);
                if ($this->isItemFolder($subitem)) {
                    $this->convertItem($subitem);
                } else {
                    $this->writeRequest($this->initRequest($subitem), $path);
                }
                array_pop($dir_tree);
            }
        } else {
            $this->writeRequest($this->initRequest($item));
        }
    }

    /**
     * Initialiazes request object to be written in file
     *
     * @param object $item
     * @return RequestContract
     * @throws InvalidHttpVersionException
     */
    protected function initRequest(object $item): RequestContract
    {
        $request_class = static::REQUEST_CLASS;

        /** @var RequestContract $request */
        $request = new $request_class();
        $request->setName($item->name);
        $request->setHttpVersion(1.1); //TODO http version?
        $request->setDescription($item->request?->description ?? null);
        $request->setVerb($item->request->method);
        $request->setUrl($item->request->url->raw);
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
        $filedir = sprintf('%s%s%s', $this->outputPath, DIRECTORY_SEPARATOR, $subpath);
        $filedir = FileSystem::makeDir($filedir);
        $filepath = sprintf('%s%s%s.%s', $filedir, DIRECTORY_SEPARATOR, $request->getName(), static::FILE_EXT);
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
        if (!$this->env?->hasVars()) {
            return $content;
        }
        $matches = [];
        if (preg_match_all('/\{\{.*}}/m', $content, $matches, PREG_PATTERN_ORDER) > 0) {
            foreach ($matches[0] as $key => $var) {
                if (str_contains($content, $var)) {
                    $content = str_replace($var, $this->env[$var] ?: $var, $content);
                    unset($matches[0][$key]);
                }
            }
        }
        return $content;
    }
}
