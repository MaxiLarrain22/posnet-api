<?php

namespace App\Http\Controllers\Posnet;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class TransactionHistoryController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|digits:8|exists:credit_cards,number',
        ]);

        $card = CreditCard::where('number', $validated['card_number'])->first();

        $transactions = Transaction::where('credit_card_id', $card->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                return [
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'purchase_amount' => number_format($transaction->purchase_amount, 2, '.', ''),
                    'fees' => $transaction->fees,
                    'interest_rate' => number_format($transaction->interest_rate * 100, 0) . '%',
                    'total_amount' => number_format($transaction->total_amount, 2, '.', ''),
                    'installment_amount' => number_format($transaction->installment_amount, 2, '.', ''),
                    'status' => $transaction->status
                ];
            });

        return $this->successResponse([
            'card_holder' => "{$card->first_name} {$card->last_name}",
            'card_number' => $card->number,
            'transactions' => $transactions
        ]);
    }
}
