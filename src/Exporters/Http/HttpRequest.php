<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Http;

use PmConverter\Exporters\Abstract\AbstractRequest;

/**
 * Class to determine file content with http request format
 */
class HttpRequest extends AbstractRequest
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
        $output[] = "$this->verb $this->url $this->http";
        foreach ($this->headers as $header_key => $header) {
            $output[] = sprintf('%s%s: %s', $header['disabled'] ? '# ' : '', $header_key, $header['value']);
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
                $output = [''];
                foreach ($this->body as $data) {
                    $output[] = sprintf(
                        '%s%s=%s',
                        empty($data->disabled) ? '' : '# ',
                        $data->key,
                        $data->type === 'file' ? $data->src : $data->value
                    );
                }
                return $output;
            default:
                return ['', $this->body];
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $output = array_merge(
            $this->prepareDescription(),
            $this->prepareHeaders(),
            $this->prepareBody()
        );
        return implode(PHP_EOL, $output);
    }
}
