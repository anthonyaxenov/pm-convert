<?php

declare(strict_types=1);

namespace PmConverter\Converters;


use PmConverter\Converters\{
    Curl\CurlConverter,
    Http\HttpConverter,
    Postman20\Postman20Converter,
    Postman21\Postman21Converter,
    Wget\WgetConverter};

enum ConvertFormat: string
{
    case Http = HttpConverter::class;
    case Curl = CurlConverter::class;
    case Wget = WgetConverter::class;
    case Postman20 = Postman20Converter::class;
    case Postman21 = Postman21Converter::class;
}
