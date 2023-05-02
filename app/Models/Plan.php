<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'stripe_id',
        'product_id',
        'name',
        'description',
        'interval',
        'interval_unit',
        'price',
        'active'
    ];
}
