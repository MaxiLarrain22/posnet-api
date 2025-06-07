<?php

namespace App\Http\Controllers\Posnet;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Exception;

class CreateCardController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:VISA,AMEX',
                'bank_name' => 'required|string|max:255',
                'number' => 'required|digits:8|unique:credit_cards,number',
                'credit_limit' => 'required|numeric|min:0',
                'dni' => 'required|string|max:20',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
            ]);

            $validated['available_credit'] = $validated['credit_limit'];

            $card = CreditCard::create($validated);

            return $this->successResponse([
                'message' => 'Card registered successfully',
                'card' => $card
            ], 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
