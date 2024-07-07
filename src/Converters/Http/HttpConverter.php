<?php

declare(strict_types=1);

namespace PmConverter\Converters\Http;

use PmConverter\Converters\Abstract\AbstractConverter;

class HttpConverter extends AbstractConverter
{
    protected const FILE_EXT = 'http';

    protected const OUTPUT_DIR = 'http';

    protected const REQUEST_CLASS = HttpRequest::class;
}
