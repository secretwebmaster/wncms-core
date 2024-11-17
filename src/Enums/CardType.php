<?php

namespace Wncms\Enums;

enum CardType: string
{
    case CREDIT = 'credit';
    case PLAN = 'plan';
    case PRODUCT = 'product';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}