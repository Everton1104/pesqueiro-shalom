<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ficha_items', function (Blueprint $table) {
            $table->string('observacao')->nullable()->after('quantity');
            // preparo=true → item que a cozinha prepara (porção/comida).
            // preparo=false com destino=cozinha → acompanhamento que sai junto com a porção.
            $table->boolean('preparo')->default(false)->after('destino');
        });
    }

    public function down(): void
    {
        Schema::table('ficha_items', function (Blueprint $table) {
            $table->dropColumn(['observacao', 'preparo']);
        });
    }
};
