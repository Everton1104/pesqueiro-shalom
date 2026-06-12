<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();                 // valor gravado no QR Code
            $table->string('cliente')->nullable();              // obrigatório só quando há item de cozinha
            $table->string('status')->default('paga');          // paga | concluida | cancelada
            $table->string('payment_method')->nullable();       // dinheiro | pix | credito | debito
            $table->decimal('total', 10, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quem vendeu
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('concluded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas');
    }
};
