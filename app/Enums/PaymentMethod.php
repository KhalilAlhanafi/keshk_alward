<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case COD = 'cod';
    case SHAM_CASH = 'sham_cash';

    public function labelAr(): string
    {
        return match($this) {
            self::COD => 'الدفع عند الاستلام',
            self::SHAM_CASH => 'شام كاش',
        };
    }
}
