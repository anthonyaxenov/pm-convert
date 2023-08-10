<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Abstract;

use Exception;
use PmConverter\Exporters\{
    RequestContract};
use PmConverter\FileSystem;

/**
 *
 */
abstract class AbstractConverter
{
    /**
     * @var object|null
     */
    protected ?object $collection = null;

    /**
     * @var string
     */
    protected string $outputPath;

    /**
     * @throws Exception
     */
    public function convert(object $collection, string $outputPath): void
    {
        $outputPath = sprintf('%s%s%s', $outputPath, DIRECTORY_SEPARATOR, static::OUTPUT_DIR);
        $this->outputPath = FileSystem::makeDir($outputPath);
        $this->collection = $collection;
        foreach ($collection->item as $item) {
            $this->convertItem($item);
        }
    }

    /**
     * @return string
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * @param object $item
     * @return bool
     */
    protected function isItemFolder(object $item): bool
    {
        return !empty($item->item) && empty($item->request);
    }

    /**
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
     * @param object $item
     * @return RequestContract
     */
    protected function initRequest(object $item): RequestContract
    {
        $request_class = static::REQUEST_CLASS;
        $result = new $request_class();
        $result->setName($item->name);
        $result->setDescription($item->request?->description ?? null);
        $result->setVerb($item->request->method);
        $result->setUrl($item->request->url->raw);
        $result->setHeaders($item->request->header);
        if ($item->request->method !== 'GET' && !empty($item->request->body)) {
            $result->setBody($item->request->body);
        }
        return $result;
    }

    /**
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
        return file_put_contents($filepath, (string)$request) > 0;
    }
}
