<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Placed = 'placed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
}
