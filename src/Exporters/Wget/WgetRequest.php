<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Wget;

use PmConverter\Exporters\Abstract\AbstractRequest;

/**
 * Class to determine file content with wget request format
 */
class WgetRequest extends AbstractRequest
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
        switch ($this->bodymode) {
            case 'formdata':
                $params = [];
                foreach ($this->body as $data) {
                    if ($data->type === 'file') {
                        continue;
                    }
                    $params[$data->key] = $data->value;
                }
                $output[] = http_build_query($params);
                return $output;
            default:
                return ["\t'$this->body' \ "];
        }
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
                'wget \ ',
                "\t--no-check-certificate \ ",
                "\t--quiet \ ",
                "\t--timeout=0 \ ",
                "\t--method $this->verb \ ",
            ],
            $this->prepareHeaders(),
            $this->prepareBody()
        );
        $output[] = "\t'$this->url'";
        return implode(PHP_EOL, array_merge($output, ['']));
    }
}
