<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comanda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comanda_id')->constrained()->cascadeOnDelete();
            // Referência ao cardápio; mantém histórico mesmo se o item for removido
            $table->foreignId('cardapio_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');               // snapshot do nome
            $table->decimal('unit_price', 8, 2);  // snapshot do preço no momento do pedido
            $table->integer('quantity')->default(1);
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comanda_items');
    }
};
