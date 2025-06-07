<?php

namespace App\Http\Controllers\Posnet;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class GetFeesController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request)
    {
        $amount = $request->validate([
            'amount' => 'required|numeric|min:0'
        ])['amount'];

        $fees = [];
        for ($i = 1; $i <= 6; $i++) {
            $interestRate = $i > 1 ? 0.03 * ($i - 1) : 0;
            $totalAmount = $amount * (1 + $interestRate);

            $fees[] = [
                'installments' => $i,
                'interest_rate' => number_format($interestRate * 100, 0) . '%',
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'installment_amount' => number_format($totalAmount / $i, 2, '.', '')
            ];
        }

        return $this->successResponse([
            'fees' => $fees
        ]);
    }
}
