<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case WaitingOnAdmin = 'waiting_on_admin';
    case WaitingOnCustomer = 'waiting_on_customer';
    case Closed = 'closed';
}
