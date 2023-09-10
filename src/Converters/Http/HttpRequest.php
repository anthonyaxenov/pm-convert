<?php

declare(strict_types=1);

namespace PmConverter\Converters\Http;

use PmConverter\Converters\Abstract\AbstractRequest;
use PmConverter\Exceptions\{
    EmptyHttpVerbException};

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
     * @throws EmptyHttpVerbException
     */
    protected function prepareHeaders(): array
    {
        $output[] = sprintf('%s %s HTTP/%s', $this->getVerb(), $this->getUrl(), $this->getHttpVersion());
        foreach ($this->headers as $name => $data) {
            $output[] = sprintf('%s%s: %s', $data['disabled'] ? '# ' : '', $name, $data['value']);
        }
        return $output;
    }

    /**
     * @inheritDoc
     */
    protected function prepareBody(): array
    {
        switch ($this->getBodymode()) {
            case 'formdata':
                $output = [''];
                foreach ($this->body as $key => $data) {
                    $output[] = sprintf(
                        '%s%s=%s',
                        empty($data['disabled']) ? '' : '# ',
                        $key,
                        $data['type'] === 'file' ? '@' . $data['value'] : $data['value']
                    );
                }
                return $output;
            default:
                return ['', $this->body];
        }
    }

    /**
     * @inheritDoc
     * @throws EmptyHttpVerbException
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
