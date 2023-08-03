<?php

declare(strict_types=1);

namespace PmConverter\Exporters;

interface RequestContract
{
    public function setName(string $name): static;
    public function getName(): string;
    public function setDescription(?string $description): static;
    public function setVerb(string $verb): static;
    public function setUrl(string $url): static;
    public function setHeaders(?array $headers): static;
    public function setBodymode(string $bodymode): static;
    public function setBody(object $body): static;
    public function __toString(): string;
}
