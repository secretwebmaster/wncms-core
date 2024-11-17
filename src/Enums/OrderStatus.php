<?php

namespace Wncms\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case COMPLETED = 'completed';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}