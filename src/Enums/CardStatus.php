<?php

namespace Wncms\Enums;

enum CardStatus: string
{
    case ACTIVE = 'active';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}