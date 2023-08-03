<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Curl;

use PmConverter\Exporters\Abstract\AbstractRequest;

/**
 *
 */
class CurlRequest extends AbstractRequest
{
    /**
     * @return string
     */
    protected function prepareBody(): ?string
    {
        switch ($this->bodymode) {
            case 'formdata':
                $body = [];
                foreach ($this->body as $data) {
                    $body[] = sprintf(
                        "%s\t--form '%s=%s' \ ",
                        isset($data->disabled) ? '# ' : '',
                        $data->key,
                        $data->type === 'file' ? "@$data->src" : $data->value
                    );
                }
                return implode(PHP_EOL, $body);
            default:
                return $this->body;
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $output[] = '#!/bin/sh';
        if ($this->description) {
            $output[] = '# ' . str_replace("\n", "\n# ", $this->description);
            $output[] = '';
        }
        $output[] = "curl \ ";
        $output[] = "\t--http1.1 \ "; //TODO verb
        $output[] = "\t--request $this->verb \ ";
        $output[] = "\t--location $this->url \ ";
        foreach ($this->headers as $header_key => $header) {
            if ($header['disabled']) {
                continue;
            }
            $output[] = sprintf("\t--header '%s=%s' \ ", $header_key, $header['value']);
        }
        if (!is_null($body = $this->prepareBody())) {
            $output[] = match ($this->bodymode) {
                'formdata' => $body,
                default => "\t--data '$body'",
            };
        }
        $output[] = rtrim(array_pop($output), '\ ');
        return implode(PHP_EOL, $output);
    }
}
