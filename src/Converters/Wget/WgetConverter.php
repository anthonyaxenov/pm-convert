<?php

declare(strict_types=1);

namespace PmConverter\Converters\Wget;

use PmConverter\Converters\Abstract\AbstractConverter;

class WgetConverter extends AbstractConverter
{
    protected const FILE_EXT = 'sh';

    protected const OUTPUT_DIR = 'wget';

    protected const REQUEST_CLASS = WgetRequest::class;
}
