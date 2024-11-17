<?php

namespace Wncms\Enums;

enum CreditType: string
{
    case BALANCE = 'balance';
    case POINTS = 'points';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}