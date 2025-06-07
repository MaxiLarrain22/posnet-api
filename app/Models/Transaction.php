<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'credit_card_id',
        'purchase_amount',
        'fees',
        'interest_rate',
        'total_amount',
        'installment_amount',
        'status'
    ];

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }
}
