<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DineInOrder extends Model
{
    protected $fillable = [
        'order_id',
        'table_id',
    ];
}