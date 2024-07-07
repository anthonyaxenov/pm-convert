<?php

declare(strict_types=1);

namespace PmConverter\Converters;


use PmConverter\Converters\Curl\CurlConverter;
use PmConverter\Converters\Http\HttpConverter;
use PmConverter\Converters\Postman20\Postman20Converter;
use PmConverter\Converters\Postman21\Postman21Converter;
use PmConverter\Converters\Wget\WgetConverter;

enum ConvertFormat: string
{
    case Http = HttpConverter::class;
    case Curl = CurlConverter::class;
    case Wget = WgetConverter::class;
    case Postman20 = Postman20Converter::class;
    case Postman21 = Postman21Converter::class;

    public static function fromArg(string $arg): self
    {
        return match ($arg) {
            'http' => ConvertFormat::Http,
            'curl' => ConvertFormat::Curl,
            'wget' => ConvertFormat::Wget,
            'v2.0' => ConvertFormat::Postman20,
            'v2.1' => ConvertFormat::Postman21,
        };
    }

    public function toArg(): string
    {
        return match ($this) {
            ConvertFormat::Http => 'http',
            ConvertFormat::Curl => 'curl',
            ConvertFormat::Wget => 'wget',
            ConvertFormat::Postman20 => 'v2.0',
            ConvertFormat::Postman21 => 'v2.1',
        };
    }
}
