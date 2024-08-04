<?php

declare(strict_types=1);

namespace PmConverter\Enums;

enum CollectionVersion: string
{
    case Version20 = 'v2.0';
    case Version21 = 'v2.1';
    case Unknown = 'unknown';
}
