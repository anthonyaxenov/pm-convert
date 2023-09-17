<?php

declare(strict_types=1);

namespace PmConverter\Converters;

use PmConverter\Collection;

interface ConverterContract
{
    public function convert(Collection $collection, string $outputPath): void;
    public function getOutputPath(): string;
}
