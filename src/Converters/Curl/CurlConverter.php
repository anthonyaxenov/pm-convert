<?php

declare(strict_types=1);

namespace PmConverter\Converters\Curl;

use PmConverter\Converters\Abstract\AbstractConverter;

class CurlConverter extends AbstractConverter
{
    protected const FILE_EXT = 'sh';

    protected const OUTPUT_DIR = 'curl';

    protected const REQUEST_CLASS = CurlRequest::class;
}
