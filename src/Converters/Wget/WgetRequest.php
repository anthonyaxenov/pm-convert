<?php

declare(strict_types=1);

namespace PmConverter\Converters\Wget;

use PmConverter\Converters\Abstract\AbstractRequest;
use PmConverter\Exceptions\EmptyHttpVerbException;

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
            $output[] = sprintf("\t--header '%s: %s' \ ", $header_key, $header['value']);
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
                $output = [];
                foreach ($this->body as $key => $data) {
                    if ($data['type'] === 'file') {
                        continue;
                    }
                    $output[$key] = $data['value'];
                }
                return $output;
            default:
                return [$this->body];
        }
    }

    /**
     * @inheritDoc
     * @throws EmptyHttpVerbException
     */
    public function __toString(): string
    {
        $output = array_merge(
            ['#!/bin/sh'],
            $this->prepareDescription(),
            [
                'wget \ ',
                "\t--no-check-certificate \ ",
                "\t--timeout 0 \ ",
                "\t--method $this->verb \ ",
            ],
            $this->prepareHeaders(),
        );
        if ($this->getBodymode() === 'formdata') {
            if ($this->getBody()) {
                if ($this->getVerb() === 'GET') {
                    $output[] = sprintf("\t%s?%s", $this->getUrl(), http_build_query($this->prepareBody()));
                } else {
                    $output[] = sprintf("\t--body-data '%s' \ ", http_build_query($this->prepareBody()));
                    $output[] = sprintf("\t%s", $this->getUrl());
                }
            }
        } else {
            if ($this->getVerb() !== 'GET') {
                $output[] = sprintf("\t--body-data '%s' \ ", implode("\n", $this->prepareBody()));
                $output[] = sprintf("\t%s", $this->getUrl());
            }
        }
        return implode(EOL, array_merge($output, ['']));
    }
}
