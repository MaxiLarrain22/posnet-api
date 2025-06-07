<?php

namespace Tests\Feature;

use App\Models\CreditCard;
use App\Models\Transaction;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_transaction_history()
    {
        // Creo una tarjeta
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

        // Creo algunas transacciones
        Transaction::create([
            'credit_card_id' => $card->id,
            'purchase_amount' => 1000,
            'fees' => 1,
            'interest_rate' => 0,
            'total_amount' => 1000,
            'installment_amount' => 1000
        ]);

        Transaction::create([
            'credit_card_id' => $card->id,
            'purchase_amount' => 500,
            'fees' => 3,
            'interest_rate' => 0.06,
            'total_amount' => 530,
            'installment_amount' => 176.67
        ]);

        $response = $this->getJson("/api/transactions?card_number={$card->number}");

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verifico los datos de la tarjeta
        $this->assertEquals('Maximiliano Larrain', $data['card_holder']);
        $this->assertEquals('41111111', $data['card_number']);

        // Verifique que hay 2 transacciones
        $this->assertCount(2, $data['transactions']);

        // Chequeo que cada transacción tenga los campos correctos
        foreach ($data['transactions'] as $transaction) {
            $this->assertArrayHasKey('date', $transaction);
            $this->assertArrayHasKey('purchase_amount', $transaction);
            $this->assertArrayHasKey('fees', $transaction);
            $this->assertArrayHasKey('interest_rate', $transaction);
            $this->assertArrayHasKey('total_amount', $transaction);
            $this->assertArrayHasKey('installment_amount', $transaction);
            $this->assertArrayHasKey('status', $transaction);
        }

        // Chequeo que las transacciones esperadas esten presentes
        $this->assertContains([
            'purchase_amount' => '500.00',
            'fees' => 3,
            'interest_rate' => '6%',
            'total_amount' => '530.00',
            'installment_amount' => '176.67',
            'status' => 'completed'
        ], array_map(function ($t) {
            return array_intersect_key($t, array_flip([
                'purchase_amount',
                'fees',
                'interest_rate',
                'total_amount',
                'installment_amount',
                'status'
            ]));
        }, $data['transactions']));

        $this->assertContains([
            'purchase_amount' => '1000.00',
            'fees' => 1,
            'interest_rate' => '0%',
            'total_amount' => '1000.00',
            'installment_amount' => '1000.00',
            'status' => 'completed'
        ], array_map(function ($t) {
            return array_intersect_key($t, array_flip([
                'purchase_amount',
                'fees',
                'interest_rate',
                'total_amount',
                'installment_amount',
                'status'
            ]));
        }, $data['transactions']));
    }

    public function test_transaction_history_invalid_card()
    {
        $response = $this->getJson('/api/transactions?card_number=99999999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['card_number']);
    }

    public function test_transaction_history_empty()
    {
        $card = CreditCard::create([
            'type' => 'VISA',
            'bank_name' => 'Banco Nación',
            'first_name' => 'Alejo',
            'last_name' => 'Larrain',
            'number' => '41111112',
            'credit_limit' => 5000,
            'available_credit' => 5000,
            'dni' => '40120248'
        ]);

        $response = $this->getJson("/api/transactions?card_number={$card->number}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'card_holder' => 'Alejo Larrain',
                    'card_number' => '41111112',
                    'transactions' => []
                ]
            ]);
    }
}
