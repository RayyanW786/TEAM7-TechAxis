<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use HasFactory;

    protected $table = 'return_requests';

    public $timestamps = false;

    protected $fillable = [
        'order_item_id',
        'user_id',
        'quantity',
        'reason',
        'status',
        'processed_by',
        'approved_quantity',
        'restock',
        'requested_at',
        'processed_at',
    ];

    protected $casts = [
        'status' => ReturnStatus::class,
        'quantity' => 'integer',
        'approved_quantity' => 'integer',
        'restock' => 'boolean',
        'requested_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
