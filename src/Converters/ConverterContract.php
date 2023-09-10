<?php

declare(strict_types=1);

namespace PmConverter\Converters;

interface ConverterContract
{
    public function convert(object $collection, string $outputPath): void;
    public function getOutputPath(): string;
}
