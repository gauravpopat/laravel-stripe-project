<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;
    protected $fillable = [
        'stripe_id',
        'product_id',
        'plan_id',
        'item_id',
        'amount',
        'currency',
    ];
}
