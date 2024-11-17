<?php

namespace Wncms\Enums;

enum DiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}