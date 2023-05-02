<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'stripe_id',
        'card_brand',
        'card_last_four',
        'primary',
    ];
}
