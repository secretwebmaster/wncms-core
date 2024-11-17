<?php

namespace Wncms\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}