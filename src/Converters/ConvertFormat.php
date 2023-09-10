<?php

declare(strict_types=1);

namespace PmConverter\Converters;


use PmConverter\Converters\{
    Curl\CurlConverter,
    Http\HttpConverter,
    Wget\WgetConverter};

enum ConvertFormat: string
{
    case Http = HttpConverter::class;
    case Curl = CurlConverter::class;
    case Wget = WgetConverter::class;
}
