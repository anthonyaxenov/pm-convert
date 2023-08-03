<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Wget;

use PmConverter\Exporters\Abstract\AbstractRequest;

/**
 *
 */
class WgetRequest extends AbstractRequest
{
    /**
     * @return string
     */
    protected function prepareBody(): ?string
    {
        switch ($this->bodymode) {
            case 'formdata':
                $lines = [];
                foreach ($this->body as &$data) {
                    if ($data->type === 'file') {
                        continue;
                    }
                    $lines[$data->key] = $data->value;
                }
                $body[] = http_build_query($lines);
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
        $output[] = 'wget \ ';
        $output[] = "\t--no-check-certificate \ ";
        $output[] = "\t--quiet \ ";
        $output[] = "\t--timeout=0 \ ";
        $output[] = "\t--method $this->verb \ ";
        foreach ($this->headers as $header_key => $header) {
            if ($header['disabled']) {
                continue;
            }
            $output[] = sprintf("\t--header '%s=%s' \ ", $header_key, $header['value']);
        }
        if (!is_null($body = $this->prepareBody())) {
            $output[] = "\t--body-data '$body' \ ";
        }
        $output[] = rtrim(array_pop($output), '\ ');
        $output[] = "\t'$this->url'";
        return implode(PHP_EOL, $output);
    }
}
