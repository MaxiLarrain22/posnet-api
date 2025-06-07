<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

class DoPaymentTest extends TestCase
{
    use RefreshDatabase;
    
    //Pago exitoso de una única cuota
    public function test_successful_single_installment_payment()
    {
        $card = CreditCard::create([
            'type' => 'AMEX',
            'bank_name' => 'Banco Macro',
            'first_name' => 'Maximiliano',
            'last_name' => 'Larrain',
            'number' => '41111111',
            'credit_limit' => 3000,
            'available_credit' => 3000,
            'dni' => '40120247'
        ]);

        $payload = [
            'number' => '41111111',
            'purchase_amount' => 1500,
            'fees' => 1,
        ];

        $response = $this->postJson('/api/payments', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Payment processed successfully',
                'ticket' => [
                    'cardholder' => 'Maximiliano Larrain',
                    'total_amount' => '1500.00',
                    'installment_amount' => '1500.00'
                ]
            ]);
        $card->refresh();
        $this->assertEquals(1500, $card->available_credit);


        $this->assertDatabaseHas('transactions', [
            'credit_card_id' => $card->id,
            'purchase_amount' => 1500,
            'fees' => 1,
            'interest_rate' => 0,
            'total_amount' => 1500,
            'installment_amount' => 1500
        ]);
    }

    //Pago de prueba fallido por un limite insuficiente 
    public function test_payment_fails_due_to_insufficient_limit()
    {
        $card = CreditCard::create([
            'type' => 'AMEX',
            'bank_name' => 'Banco Macro',
            'first_name' => 'Maximiliano',
            'last_name' => 'Larrain',
            'number' => '41111111',
            'credit_limit' => 3000,
            'available_credit' => 3000,
            'dni' => '40120247'
        ]);

        $payload = [
            'number' => '41111111',
            'purchase_amount' => 3500,
            'fees' => 1,
        ];

        $response = $this->postJson('/api/payments', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Insufficient limit on the card.'
            ]);
    }

    //Prueba de pago con tarjeta inexistente
    public function test_payment_with_nonexistent_card()
    {
        $payload = [
            'number' => '411111118',
            'purchase_amount' => 2000,
            'fees' => 1,
        ];

        $response = $this->postJson('/api/payments', $payload);
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'The selected card number is not valid.'
            ]);
    }

    //Pago exitoso con múltiples cuotas 
    public function test_successful_multiple_installment_payment()
    {
        $card = CreditCard::create([
            'type' => 'AMEX',
            'bank_name' => 'Banco Macro',
            'first_name' => 'Maximiliano',
            'last_name' => 'Larrain',
            'number' => '41111111',
            'credit_limit' => 3000,
            'available_credit' => 3000,
            'dni' => '40120247'
        ]);

        $payload = [
            'number' => '41111111',
            'purchase_amount' => 1000,
            'fees' => 3,
        ];

        $response = $this->postJson('/api/payments', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'message',
                    'ticket' => [
                        'cardholder',
                        'total_amount',
                        'installment_amount'
                    ]
                ]
            ]);

        // Verifico que se creó la transacción con los valores correctos
        $transaction = \App\Models\Transaction::where('credit_card_id', $card->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(1000, $transaction->purchase_amount);
        $this->assertEquals(3, $transaction->fees);
        $this->assertEquals(0.06, $transaction->interest_rate);
        $this->assertEquals(1060, $transaction->total_amount);
        $this->assertEquals(353.33, round($transaction->installment_amount, 2));

        // Actualizó el límite disponible
        $card->refresh();
        $this->assertEquals(1940, $card->available_credit); // 3000 - 1060
    }

    //Prueba de pago fallida con cuotas inválidas
    public function test_payment_fails_with_invalid_installments()
    {
        $card = CreditCard::create([
            'type' => 'AMEX',
            'bank_name' => 'Banco Macro',
            'first_name' => 'Maximiliano',
            'last_name' => 'Larrain',
            'number' => '41111111',
            'credit_limit' => 3000,
            'available_credit' => 3000,
            'dni' => '40120247'
        ]);

        $payload = [
            'number' => '41111111',
            'purchase_amount' => 1000,
            'fees' => 12, // Más de 6 cuotas
        ];

        $response = $this->postJson('/api/payments', $payload);
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'The fees field must be between 1 and 6.'
            ]);


        $this->assertDatabaseCount('transactions', 0);


        $card->refresh();
        $this->assertEquals(3000, $card->available_credit);
    }
}
