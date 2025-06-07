<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeesCalculationTest extends TestCase
{
    public function test_fees_calculation_for_1000_pesos()
    {
        $response = $this->getJson('/api/fees?amount=1000');

        $response->assertStatus(200);

        $fees = $response->json('data.fees');

        // Verifico que devuelve 6 opciones de cuotas
        $this->assertCount(6, $fees);

        // Primera cuota (sin interés)
        $this->assertEquals([
            'installments' => 1,
            'interest_rate' => '0%',
            'total_amount' => '1000.00',
            'installment_amount' => '1000.00'
        ], $fees[0]);

        // Una cuota intermedia (3 cuotas)
        $this->assertEquals([
            'installments' => 3,
            'interest_rate' => '6%',
            'total_amount' => '1060.00',
            'installment_amount' => '353.33'
        ], $fees[2]);

        // Ultima cuota (6 cuotas)
        $this->assertEquals([
            'installments' => 6,
            'interest_rate' => '15%',
            'total_amount' => '1150.00',
            'installment_amount' => '191.67'
        ], $fees[5]);
    }

    public function test_fees_calculation_validation()
    {
        $response = $this->getJson('/api/fees');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $response = $this->getJson('/api/fees?amount=-100');
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_interest_rates_calculation()
    {
        $response = $this->getJson('/api/fees?amount=1000');
        $fees = $response->json('data.fees');

        // Verifico las tasas de interés para cada número de cuotas
        $this->assertEquals('0%', $fees[0]['interest_rate']); // 1 cuota - sin interés
        $this->assertEquals('3%', $fees[1]['interest_rate']); // 2 cuotas - 3%
        $this->assertEquals('6%', $fees[2]['interest_rate']); // 3 cuotas - 6%
        $this->assertEquals('9%', $fees[3]['interest_rate']); // 4 cuotas - 9%
        $this->assertEquals('12%', $fees[4]['interest_rate']); // 5 cuotas - 12%
        $this->assertEquals('15%', $fees[5]['interest_rate']); // 6 cuotas - 15%

        // Verifico los montos totales
        $this->assertEquals('1000.00', $fees[0]['total_amount']); // 1 cuota - 1000
        $this->assertEquals('1030.00', $fees[1]['total_amount']); // 2 cuotas - 1000 + 3%
        $this->assertEquals('1060.00', $fees[2]['total_amount']); // 3 cuotas - 1000 + 6%
        $this->assertEquals('1090.00', $fees[3]['total_amount']); // 4 cuotas - 1000 + 9%
        $this->assertEquals('1120.00', $fees[4]['total_amount']); // 5 cuotas - 1000 + 12%
        $this->assertEquals('1150.00', $fees[5]['total_amount']); // 6 cuotas - 1000 + 15%
    }
}
