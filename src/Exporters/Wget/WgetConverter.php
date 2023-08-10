<?php

declare(strict_types=1);

namespace PmConverter\Exporters\Wget;

use PmConverter\Exporters\{
    Abstract\AbstractConverter,
    ConverterContract};

class WgetConverter extends AbstractConverter implements ConverterContract
{
    protected const FILE_EXT = 'sh';

    protected const OUTPUT_DIR = 'wget';

    protected const REQUEST_CLASS = WgetRequest::class;
}
