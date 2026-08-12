<?php

namespace App\Enums;

enum SizeKey: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function labelAr(): string
    {
        return match($this) {
            self::SMALL => 'صغير',
            self::MEDIUM => 'وسط',
            self::LARGE => 'كبير',
        };
    }
}
