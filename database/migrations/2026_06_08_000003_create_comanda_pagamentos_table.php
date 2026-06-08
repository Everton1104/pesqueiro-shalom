<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comanda_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained()->cascadeOnDelete();
            $table->string('descricao')->nullable();          // ex: "Casal", "Pessoa 1"
            $table->decimal('valor', 10, 2);                  // valor acertado
            $table->string('payment_method');                 // dinheiro | pix | credito | debito
            $table->boolean('is_final')->default(false);      // pagamento gerado no fechamento
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quem registrou
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_pagamentos');
    }
};
