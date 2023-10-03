<?php

declare(strict_types=1);

namespace PmConverter\Converters\Abstract;

use PmConverter\Converters\RequestContract;
use PmConverter\Exceptions\{
    EmptyHttpVerbException,
    InvalidHttpVersionException};
use PmConverter\HttpVersion;
use Stringable;

/**
 * Class to determine file content with any request format
 */
abstract class AbstractRequest implements Stringable, RequestContract
{
    /**
     * @var string HTTP verb (GET, POST, etc.)
     */
    protected string $verb;

    /**
     * @var string URL where to send a request
     */
    protected string $url;

    /**
     * @var float HTTP protocol version
     */
    protected float $httpVersion = 1.1;

    /**
     * @var string Request name
     */
    protected string $name;

    /**
     * @var string|null Request description
     */
    protected ?string $description = null;

    /**
     * @var array Request headers
     */
    protected array $headers = [];

    /**
     * @var mixed Request body
     */
    protected mixed $body = null;

    /**
     * @var string Request body type
     */
    protected string $bodymode = 'raw';

    /**
     * @inheritDoc
     */
    public function setHttpVersion(float $version): static
    {
        if (!in_array($version, HttpVersion::values())) {
            throw new InvalidHttpVersionException(
                'Only these HTTP versions are supported: ' . implode(', ', HttpVersion::values())
            );
        }
        $this->httpVersion = $version;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHttpVersion(): float
    {
        return $this->httpVersion;
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return str_replace(DS, '_', $this->name);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     */
    public function setVerb(string $verb): static
    {
        $this->verb = $verb;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVerb(): string
    {
        empty($this->verb) && throw new EmptyHttpVerbException('Request HTTP verb must be defined before conversion');
        return $this->verb;
    }

    /**
     * @inheritDoc
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return $this->url ?: '<empty url>';
    }

    /**
     * @inheritDoc
     */
    public function setHeaders(?array $headers): static
    {
        foreach ($headers as $header) {
            $this->setHeader($header->key, $header->value, $header?->disabled ?? false);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setHeader(string $name, mixed $value, bool $disabled = false): static
    {
        $this->headers[$name] = [
            'value' => $value,
            'disabled' => $disabled,
        ];
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function setAuth(?object $auth): static
    {
        if (!empty($auth)) {
            switch ($auth->type) {
                case 'bearer':
                    $this->setHeader('Authorization', 'Bearer ' . $auth->{$auth->type}[0]->value);
                    break;
                default:
                    break;
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setBodymode(string $bodymode): static
    {
        $this->bodymode = $bodymode;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBodymode(): string
    {
        return $this->bodymode;
    }

    /**
     * @inheritDoc
     */
    public function setBody(object $body): static
    {
        $this->setBodymode($body->mode);
        if ($body->mode === 'formdata') {
            $this->setHeader('Content-Type', 'multipart/form-data')->setFormdataBody($body);
        } elseif ($body->mode === 'raw') {
            $this->setBodyAsIs($body);
            if (!empty($body->options) && $body->options->{$this->bodymode}->language === 'json') {
                $this->setHeader('Content-Type', 'application/json');
            }
        }
        return $this;
    }

    /**
     * Sets body content from multipart/formdata
     *
     * @param object $body
     * @return $this
     */
    protected function setFormdataBody(object $body): static
    {
        foreach ($body->formdata as $field) {
            $this->body[$field->key] = [
                'value' => $field->type === 'file' ? $field->src : $field->value,
                'type' => $field->type,
            ];
        }
        return $this;
    }

    /**
     * Sets body content from application/json
     *
     * @param object $body
     * @return $this
     */
    protected function setBodyAsIs(object $body): static
    {
        $this->body = $body->{$this->getBodymode()};
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): mixed
    {
        return $this->body;
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
