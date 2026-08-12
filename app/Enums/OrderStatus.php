<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function labelAr(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::CONFIRMED => 'تم التأكيد',
            self::PROCESSING => 'قيد التجهيز',
            self::OUT_FOR_DELIVERY => 'خارج للتوصيل',
            self::DELIVERED => 'تم التوصيل',
            self::CANCELLED => 'ملغي',
        };
    }
}
