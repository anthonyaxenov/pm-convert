<?php

declare(strict_types=1);

namespace PmConverter\Converters\Curl;

use PmConverter\Converters\{
    Abstract\AbstractConverter,
    ConverterContract};

class CurlConverter extends AbstractConverter implements ConverterContract
{
    protected const FILE_EXT = 'sh';

    protected const OUTPUT_DIR = 'curl';

    protected const REQUEST_CLASS = CurlRequest::class;
}
