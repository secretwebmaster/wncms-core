<?php

namespace Wncms\Enums;

enum TransactionType: string
{
    case EARN = 'earn';
    case SPEND = 'spend';
    case RECHARGE = 'recharge';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';

    /**
     * Get all enum values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}