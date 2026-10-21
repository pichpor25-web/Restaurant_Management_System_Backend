<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $fillable = [
    'order_number', 'user_id', 'order_type', 'status',
    'subtotal', 'discount', 'tax', 'total', 'notes',
];

protected $casts = [
    'subtotal' => 'decimal:2',
    'discount' => 'decimal:2',
    'tax' => 'decimal:2',
    'total' => 'decimal:2',
];
}
