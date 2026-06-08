<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comandas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();              // valor gravado no QR Code
            $table->string('cliente');
            $table->string('status')->default('aberta');      // aberta | fechada | cancelada
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // quem abriu
            $table->string('observacao')->nullable();
            $table->string('payment_method')->nullable();     // dinheiro | pix | cartao
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);      // valor final, gravado no fechamento
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comandas');
    }
};
