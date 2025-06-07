<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    protected $fillable = [
        'type',
        'bank_name',
        'number',
        'credit_limit',
        'available_credit',
        'dni',
        'first_name',
        'last_name',
    ];
}
