<?php

namespace App\Models;

use App\Enums\TicketKind;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $table = 'support_tickets';

    protected $fillable = [
        'created_by_user_id',
        'subject',
        'status',
        'ticket_kind',
        'order_id',
        'order_item_id',
        'product_id',
        'variant_id',
        'assigned_to_user_id',
        'last_message_at',
        'closed_at',
        'closed_by_user_id',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'ticket_kind' => TicketKind::class,
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id')->orderBy('created_at');
    }
}
