<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Curl;

use PmConverter\Exporters\Abstract\AbstractRequest;

/**
 * Class to determine file content with curl request format
 */
class CurlRequest extends AbstractRequest
{
    /**
     * @inheritDoc
     */
    protected function prepareDescription(): array
    {
        return empty($this->description)
            ? []
            : ['# ' . str_replace("\n", "\n# ", $this->description), ''];
    }

    /**
     * @inheritDoc
     */
    protected function prepareHeaders(): array
    {
        $output = [];
        foreach ($this->headers as $header_key => $header) {
            if ($header['disabled']) {
                continue;
            }
            $output[] = sprintf("\t--header '%s=%s' \ ", $header_key, $header['value']);
        }
        return $output;
    }

    /**
     * @inheritDoc
     */
    protected function prepareBody(): array
    {
        $output = [];
        switch ($this->bodymode) {
            case 'formdata':
                foreach ($this->body as $data) {
                    $output[] = sprintf(
                        "%s\t--form '%s=%s' \ ",
                        isset($data->disabled) ? '# ' : '',
                        $data->key,
                        $data->type === 'file' ? "@$data->src" : $data->value
                    );
                }
                break;
            default:
                $output = ["\t--data '$this->body'"];
                break;
        }
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $output = array_merge(
            ['#!/bin/sh'],
            $this->prepareDescription(),
            [
                "curl \ ",
                "\t--http1.1 \ ", //TODO proto
                "\t--request $this->verb \ ",
                "\t--location $this->url \ ",
            ],
            $this->prepareHeaders(),
            $this->prepareBody()
        );
        $output[] = rtrim(array_pop($output), '\ ');
        return implode(PHP_EOL, array_merge($output, ['']));
    }
}
