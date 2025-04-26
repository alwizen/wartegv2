<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_date',
        'order_number',
        'customer_name',
        'customer_phone',
        'total_amount',
        'payment_method',
        'payment_status',
        'payment_due_date',
        'notes'
    ];

    protected $casts = [
        'payment_due_date' => 'date',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
