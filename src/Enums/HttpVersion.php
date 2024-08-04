<?php

declare(strict_types=1);

namespace PmConverter\Enums;

enum HttpVersion: string
{
    case Version10 = '1.0';
    case Version11 = '1.1';
    case Version2 = '2';
    case Version3 = '3';

    public static function values(): array
    {
        return array_combine(
            array_column(self::cases(), 'name'),
            array_column(self::cases(), 'value'),
        );
    }
}
