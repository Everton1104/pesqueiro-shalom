<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comanda_items', function (Blueprint $table) {
            // Campos para a fila da Cozinha (espelham ficha_items). A comanda não tem balcão,
            // então só precisamos saber se o item exige preparo (preparo) e seu status de entrega.
            $table->string('category')->nullable()->after('name');          // snapshot da categoria
            $table->boolean('preparo')->default(false)->after('observacao'); // true = vai pra cozinha
            $table->string('status')->default('pendente')->after('preparo'); // pendente | entregue
            $table->timestamp('delivered_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('comanda_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'preparo', 'status', 'delivered_at']);
        });
    }
};
