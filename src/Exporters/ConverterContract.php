<?php

declare(strict_types=1);

namespace PmConverter\Exporters;

interface ConverterContract
{
    public function convert(object $collection, string $outputPath): void;
    public function getOutputPath(): string;
}
