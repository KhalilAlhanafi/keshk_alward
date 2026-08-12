<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AWAITING_VERIFICATION = 'awaiting_verification';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function labelAr(): string
    {
        return match($this) {
            self::PENDING => 'قيد الانتظار',
            self::AWAITING_VERIFICATION => 'بانتظار التحقق',
            self::VERIFIED => 'تم التحقق',
            self::REJECTED => 'مرفوض',
            self::PAID => 'مدفوع',
            self::FAILED => 'فشل الدفع',
            self::REFUNDED => 'مسترد',
        };
    }
}
