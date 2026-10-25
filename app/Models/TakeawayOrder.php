<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TakeawayOrder extends Model
{
    protected $fillable = [
        'order_id',
        'customer_name',
        'customer_phone',
    ];
}