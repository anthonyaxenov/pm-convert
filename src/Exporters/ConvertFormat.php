<?php

declare(strict_types=1);

namespace PmConverter\Exporters;


use PmConverter\Exporters\Curl\CurlConverter;
use PmConverter\Exporters\Http\HttpConverter;
use PmConverter\Exporters\Wget\WgetConverter;

enum ConvertFormat: string
{
    case Http = HttpConverter::class;
    case Curl = CurlConverter::class;
    case Wget = WgetConverter::class;
}
