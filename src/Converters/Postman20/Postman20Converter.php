<?php

declare(strict_types=1);

namespace PmConverter\Converters\Postman20;

use PmConverter\Collection;
use PmConverter\Converters\{
    Abstract\AbstractConverter,
    ConverterContract};
use PmConverter\Exceptions\CannotCreateDirectoryException;
use PmConverter\Exceptions\DirectoryIsNotWriteableException;
use PmConverter\FileSystem;

/**
 * Converts Postman Collection v2.1 to v2.0
 */
class Postman20Converter extends AbstractConverter implements ConverterContract
{
    protected const FILE_EXT = 'v20.postman_collection.json';

    protected const OUTPUT_DIR = 'v2.0';

    /**
     * Converts collection requests
     *
     * @param Collection $collection
     * @param string $outputPath
     * @return void
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     */
    public function convert(Collection $collection, string $outputPath): void
    {
        $this->collection = $collection;
        $this->collection->info->schema = str_replace('/v2.1.', '/v2.0.', $this->collection->info->schema);
        $this->prepareOutputDir($outputPath);
        $this->convertAuth($this->collection->raw());
        foreach ($this->collection->item as $item) {
            $this->convertItem($item);
        }
        $this->writeCollection();
    }

    /**
     * Writes converted collection into file
     *
     * @return bool
     * @throws CannotCreateDirectoryException
     * @throws DirectoryIsNotWriteableException
     */
    protected function writeCollection(): bool
    {
        $filedir = FileSystem::makeDir($this->outputPath);
        $filepath = sprintf('%s%s%s.%s', $filedir, DIRECTORY_SEPARATOR, $this->collection->name(), static::FILE_EXT);
        return file_put_contents($filepath, $this->collection) > 0;
    }

    /**
     * Changes some requests fields in place
     *
     * @param mixed $item
     * @return void
     */
    protected function convertItem(mixed $item): void
    {
        if ($this->isItemFolder($item)) {
            foreach ($item->item as $subitem) {
                if ($this->isItemFolder($subitem)) {
                    $this->convertItem($subitem);
                } else {
                    $this->convertAuth($subitem->request);
                    $this->convertRequestUrl($subitem->request);
                    $this->convertResponseUrls($subitem->response);
                }
            }
        } else {
            $this->convertAuth($item->request);
            $this->convertRequestUrl($item->request);
            $this->convertResponseUrls($item->response);
        }
    }

    /**
     * Converts auth object from v2.1 to v2.0
     *
     * @param object $request
     * @return void
     */
    protected function convertAuth(object $request): void
    {
        if (empty($request->auth)) {
            return;
        }
        $type = $request->auth->type;
        if ($type !== 'noauth' && is_array($request->auth->$type)) { // bearer
            $auth = [];
            foreach ($request->auth->$type as $param) {
                $auth[$param->key] = $param->value;
            }
            $request->auth->$type = (object)$auth;
        }
    }

    /**
     * Converts requests URLs from object v2.1 to string v2.0
     *
     * @param object $request
     * @return void
     */
    protected function convertRequestUrl(object $request): void
    {
        if (is_object($request->url)) {
            $request->url = $request->url->raw;
        }
    }

    /**
     * Converts URLs response examples from object v2.1 to string v2.0
     *
     * @param array $responses
     * @return void
     */
    protected function convertResponseUrls(array $responses): void
    {
        foreach ($responses as $response) {
            if (is_object($response->originalRequest->url)) {
                $raw = $response->originalRequest->url->raw;
                $response->originalRequest->url = $raw;
            }
        }
    }
}
