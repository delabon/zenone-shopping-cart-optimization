<?php

declare(strict_types=1);

namespace App\Enums;

enum CartStatus: string
{
    case Active = 'active';
    case Optimized = 'optimized';
    case Checkout = 'checkout';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}

