<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ficha_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_id')->constrained()->cascadeOnDelete();
            // Referência ao cardápio; mantém histórico mesmo se o item for removido
            $table->foreignId('cardapio_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');               // snapshot do nome
            $table->string('category')->nullable(); // snapshot da categoria
            $table->decimal('unit_price', 8, 2);  // snapshot do preço
            $table->integer('quantity')->default(1);
            $table->string('destino')->default('balcao'); // balcao | cozinha
            $table->string('status')->default('pendente'); // pendente | entregue
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_items');
    }
};
