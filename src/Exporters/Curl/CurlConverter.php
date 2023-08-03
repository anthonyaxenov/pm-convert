<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Curl;

use PmConverter\Exporters\{
    Abstract\AbstractConverter,
    ConverterContract};

class CurlConverter extends AbstractConverter implements ConverterContract
{
    protected const FILE_EXT = 'sh';
    protected const OUTPUT_DIR = 'curl';

    protected const REQUEST = CurlRequest::class;
}
