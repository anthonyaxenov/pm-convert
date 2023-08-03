<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Abstract;

use PmConverter\Exporters\Http\HttpRequest;
use PmConverter\Exporters\RequestContract;

/**
 *
 */
abstract class AbstractRequest implements RequestContract
{
    /**
     * @var string
     */
    protected string $http = 'HTTP/1.1'; //TODO verb

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
     * @param string $name
     * @return HttpRequest
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '_', $this->name);
    }

    /**
     * @param string|null $description
     * @return HttpRequest
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $verb
     * @return HttpRequest
     */
    public function setVerb(string $verb): static
    {
        $this->verb = $verb;
        return $this;
    }

    /**
     * @param string $url
     * @return HttpRequest
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
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
     * @param string $bodymode
     * @return HttpRequest
     */
    public function setBodymode(string $bodymode): static
    {
        $this->bodymode = $bodymode;
        return $this;
    }

    /**
     * @param string $body
     * @return HttpRequest
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
     * @return string
     */
    abstract protected function prepareBody(): ?string;

    /**
     * @return string
     */
    abstract public function __toString(): string;
}
