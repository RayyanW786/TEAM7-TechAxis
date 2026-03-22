<?php

namespace App\Enums;

enum TicketKind: string
{
    case General = 'general';
    case ProductSupport = 'product_support';
    case RefundRequest = 'refund_request';
}
