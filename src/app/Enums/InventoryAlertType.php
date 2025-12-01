<?php

namespace App\Enums;

enum InventoryAlertType: string
{
    case OutOfStock = 'OUT_OF_STOCK';
    case LowStock = 'LOW_STOCK';
    case InStock = 'IN_STOCK';
}
