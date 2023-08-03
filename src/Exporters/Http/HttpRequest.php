<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Http;

use PmConverter\Exporters\Abstract\AbstractRequest;

/**
 *
 */
class HttpRequest extends AbstractRequest
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
                        '%s%s=%s',
                        empty($data->disabled) ? '' : '# ',
                        $data->key,
                        $data->type === 'file' ? "$data->src" : $data->value
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
        if ($this->description) {
            $output[] = '# ' . str_replace("\n", "\n# ", $this->description);
            $output[] = '';
        }
        $output[] = "$this->verb $this->url $this->http";
        foreach ($this->headers as $header_key => $header) {
            $output[] = sprintf('%s%s: %s', $header['disabled'] ? '# ' : '', $header_key, $header['value']);
        }
        $output[] = '';
        $output[] = (string)$this->prepareBody();
        return implode(PHP_EOL, $output);
    }
}
