<?php

declare(strict_types=1);

namespace PmConverter\Converters;

use PmConverter\Converters\Http\HttpRequest;
use PmConverter\Exceptions\{
    EmptyHttpVerbException,
    InvalidHttpVersionException};

interface RequestContract
{
    /**
     * Sets HTTP protocol version
     *
     * @param float $version
     * @return $this
     * @throws InvalidHttpVersionException
     */
    public function setHttpVersion(float $version): static;

    /**
     * Returns HTTP protocol version
     *
     * @return float
     */
    public function getHttpVersion(): float;

    /**
     * Sets name from collection item to request object
     *
     * @param string $name
     * @return HttpRequest
     */
    public function setName(string $name): static;

    /**
     * Returns name of request
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Sets description from collection item to request object
     *
     * @param string|null $description
     * @return HttpRequest
     */
    public function setDescription(?string $description): static;

    /**
     * Returns description request
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Sets HTTP verb from collection item to request object
     *
     * @param string $verb
     * @return HttpRequest
     */
    public function setVerb(string $verb): static;

    /**
     * Returns HTTP verb of request
     *
     * @return string
     * @throws EmptyHttpVerbException
     */
    public function getVerb(): string;

    /**
     * Sets URL from collection item to request object
     *
     * @param string $url
     * @return HttpRequest
     */
    public function setUrl(string $url): static;

    /**
     * Returns URL of request
     *
     * @return string
     */
    public function getRawUrl(): string;

    /**
     * Sets headers from collection item to request object
     *
     * @param object[]|null $headers
     * @return $this
     */
    public function setHeaders(?array $headers): static;

    /**
     * Sets one header to request object
     *
     * @param string $name Header's name
     * @param mixed $value Header's value
     * @param bool $disabled Pass true to skip (or comment out) this header
     * @return $this
     */
    public function setHeader(string $name, mixed $value, bool $disabled = false): static;

    /**
     * Returns array of prepared headers
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Sets authorization headers
     *
     * @param object $auth
     * @return $this
     */
    public function setAuth(object $auth): static;

    /**
     * Sets body mode from collection item to request object
     *
     * @param string $bodymode
     * @return HttpRequest
     */
    public function setBodymode(string $bodymode): static;

    /**
     * Returns body mode of request
     *
     * @return HttpRequest
     */
    public function getBodymode(): string;

    /**
     * Sets body from collection item to request object
     *
     * @param object $body
     * @return $this
     */
    public function setBody(object $body): static;

    /**
     * Returns body content
     *
     * @return mixed
     */
    public function getBody(): mixed;
}
