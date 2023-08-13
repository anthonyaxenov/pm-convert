<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Abstract;

use PmConverter\Exporters\Http\HttpRequest;
use PmConverter\Exporters\RequestContract;

/**
 * Class to determine file content with any request format
 */
abstract class AbstractRequest implements RequestContract
{
    /**
     * @var string
     */
    protected string $http = 'HTTP/1.1'; //TODO proto

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * @var array
     */
    protected array $headers = [];

    /**
     * @var mixed
     */
    protected mixed $body = null;

    /**
     * @var string
     */
    protected string $bodymode = 'raw';

    /**
     * @var string
     */
    protected string $verb;

    /**
     * @var string
     */
    protected string $url;

    /**
     * Sets name from collection item to request object
     *
     * @param string $name
     * @return HttpRequest
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns name of request
     *
     * @return string
     */
    public function getName(): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '_', $this->name);
    }

    /**
     * Sets description from collection item to request object
     *
     * @param string|null $description
     * @return HttpRequest
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Sets HTTP verb from collection item to request object
     *
     * @param string $verb
     * @return HttpRequest
     */
    public function setVerb(string $verb): static
    {
        $this->verb = $verb;
        return $this;
    }

    /**
     * Sets URL from collection item to request object
     *
     * @param string $url
     * @return HttpRequest
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Sets headers from collection item to request object
     *
     * @param object[]|null $headers
     * @return $this
     */
    public function setHeaders(?array $headers): static
    {
        foreach ($headers as $header) {
            $this->headers[$header->key] = [
                'value' => $header->value,
                'disabled' => $header?->disabled ?? false,
            ];
        }
        return $this;
    }

    /**
     * Sets authorization headers
     *
     * @param object|null $auth
     * @return $this
     */
    public function setAuth(?object $auth): static
    {
        if (!empty($auth)) {
            switch ($auth->type) {
                case 'bearer':
                    $this->headers['Authorization'] = [
                        'value' => 'Bearer ' . $auth->{$auth->type}[0]->value,
                        'disabled' => false,
                    ];
                    break;
                default:
                    break;
            }
        }
        return $this;
    }


    /**
     * Sets body mode from collection item to request object
     *
     * @param string $bodymode
     * @return HttpRequest
     */
    public function setBodymode(string $bodymode): static
    {
        $this->bodymode = $bodymode;
        return $this;
    }

    /**
     * Sets body from collection item to request object
     *
     * @param object $body
     * @return $this
     */
    public function setBody(object $body): static
    {
        $this->setBodymode($body->mode);
        if (!empty($body->options) && $body->options->{$this->bodymode}->language === 'json') {
            empty($this->headers['Content-Type']) && $this->setHeaders([
                (object)[
                    'key' => 'Content-Type',
                    'value' => 'application/json',
                    'disabled' => false,
                ],
            ]);
        }
        $body->mode === 'formdata' && $this->setHeaders([
            (object)[
                'key' => 'Content-Type',
                'value' => 'multipart/form-data',
                'disabled' => false,
            ],
        ]);
        $this->body = $body->{$body->mode};
        return $this;
    }

    /**
     * Returns array of description lines
     *
     * @return array
     */
    abstract protected function prepareDescription(): array;

    /**
     * Returns array of headers
     *
     * @return array
     */
    abstract protected function prepareHeaders(): array;

    /**
     * Returns array of request body lines
     *
     * @return array
     */
    abstract protected function prepareBody(): array;

    /**
     * Converts request object to string to be written in result file
     *
     * @return string
     */
    abstract public function __toString(): string;
}
