<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Http;

use PmConverter\Exporters\{
    Abstract\AbstractConverter,
    ConverterContract};

class HttpConverter extends AbstractConverter implements ConverterContract
{
    protected const FILE_EXT = 'http';
    protected const OUTPUT_DIR = 'http';

    protected const REQUEST = HttpRequest::class;
}
