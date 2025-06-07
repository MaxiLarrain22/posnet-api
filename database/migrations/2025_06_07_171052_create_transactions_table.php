<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained()->onDelete('cascade');
            $table->decimal('purchase_amount', 10, 2);
            $table->integer('fees'); //Honorarios se refieren a la cantidad de cuotas en las que se divide un pago
            $table->decimal('interest_rate', 5, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('installment_amount', 10, 2);
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
