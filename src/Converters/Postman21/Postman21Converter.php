<?php

declare(strict_types=1);

namespace PmConverter\Converters\Postman21;

use PmConverter\Collection;
use PmConverter\Converters\{
    Abstract\AbstractConverter,
    ConverterContract};
use PmConverter\Exceptions\CannotCreateDirectoryException;
use PmConverter\Exceptions\DirectoryIsNotWriteableException;
use PmConverter\FileSystem;

/**
 * Converts Postman Collection v2.0 to v2.1
 */
class Postman21Converter extends AbstractConverter implements ConverterContract
{
    protected const FILE_EXT = 'v21.postman_collection.json';

    protected const OUTPUT_DIR = 'pm-v2.1';

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
        $this->collection->info->schema = str_replace('/v2.0.', '/v2.1.', $this->collection->info->schema);
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
     * Converts auth object from v2.0 to v2.1
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
        if ($type !== 'noauth' && isset($request->auth->$type)) {
            $auth = [];
            foreach ($request->auth->$type as $key => $value) {
                $auth[] = (object)[
                    'key' => $key,
                    'value' => $value,
                    'type' => 'string',
                ];
            }
            $request->auth->$type = $auth;
        }
    }

    /**
     * Converts requests URLs from string v2.0 to object v2.1
     *
     * @param object $request
     * @return void
     */
    protected function convertRequestUrl(object $request): void
    {
        if (is_string($request->url) && mb_strlen($request->url) > 0) {
            $data = array_values(array_filter(explode('/', $request->url))); //TODO URL parsing
            if (count($data) === 1) {
                $url = [
                    'raw' => $request->url,
                    'host' => [$data[0] ?? $request->url],
                ];
            } else {
                $url = [
                    'raw' => $request->url,
                    'protocol' => str_replace(':', '', $data[0]),
                    'host' => [$data[1] ?? $request->url],
                    'path' => array_slice($data, 2),
                ];
            }
            $request->url = (object)$url;
        }
    }

    /**
     * Converts URLs response examples from string v2.0 to object v2.1
     *
     * @param array $responses
     * @return void
     */
    protected function convertResponseUrls(array $responses): void
    {
        foreach ($responses as $response) {
            if (is_string($response->originalRequest->url)) {
                $data = array_values(array_filter(explode('/', $response->originalRequest->url))); //TODO URL parsing
                if (count($data) === 1) {
                    $url = [
                        'raw' => $response->originalRequest->url,
                        'host' => [$data[0] ?? $response->originalRequest->url],
                    ];
                } else {
                    $url = [
                        'raw' => $response->originalRequest->url,
                        'protocol' => str_replace(':', '', $data[0]),
                        'host' => [$data[1] ?? $response->originalRequest->url],
                        'path' => array_slice($data, 2),
                    ];
                }
                $response->originalRequest->url = (object)$url;
            }
        }
    }
}
