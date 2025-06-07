<?php

namespace App\Http\Controllers\Posnet;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Exception;
use App\Http\Controllers\Posnet\GetFeesController;

class DoPaymentController extends Controller
{
    use ApiResponser;

    public function __invoke(Request $request)
    {
        try {
            $messages = [
                'number.exists' => 'The selected card number is not valid.',
                'number.digits' => 'The selected card number is not valid.',
            ];

            $validated = $request->validate([
                'number' => 'required|digits:8|exists:credit_cards,number',
                'purchase_amount' => 'required|numeric|min:0',
                'fees' => 'required|integer|between:1,6',
            ], $messages);

            $card = CreditCard::where('number', $validated['number'])->first();

            // Obtengo el cálculo de cuotas del GetFeesController
            $feesController = new GetFeesController();
            $feesResponse = $feesController->__invoke(new Request(['amount' => $validated['purchase_amount']]));
            $feesData = $feesResponse->getData()->data->fees;

            // Busco la opción de la cuota seleccionada
            $selectedFee = collect($feesData)->firstWhere('installments', $validated['fees']);

            if (!$selectedFee) {
                throw new Exception("Invalid number of installments.", 422);
            }

            $totalAmount = floatval(str_replace(',', '', $selectedFee->total_amount));
            $interestRate = floatval(str_replace(['%', ','], '', $selectedFee->interest_rate)) / 100;

            if ($card->available_credit < $totalAmount) {
                throw new Exception("Insufficient limit on the card.", 422);
            }

            $card->available_credit -= $totalAmount;
            $card->save();

            // Registro la transacción
            Transaction::create([
                'credit_card_id' => $card->id,
                'purchase_amount' => $validated['purchase_amount'],
                'fees' => $validated['fees'],
                'interest_rate' => $interestRate,
                'total_amount' => $totalAmount,
                'installment_amount' => $totalAmount / $validated['fees'],
            ]);

            $ticket = [
                'cardholder' => "{$card->first_name} {$card->last_name}", //Nombre y Apellido del cliente
                'total_amount' => number_format($totalAmount, 2, '.', ''), //Monto total a pagar 
                'installment_amount' => number_format($totalAmount / $validated['fees'], 2, '.', ''), //Monto de cada cuota
            ];

            return $this->successResponse([
                'message' => 'Payment processed successfully',
                'ticket' => $ticket
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
