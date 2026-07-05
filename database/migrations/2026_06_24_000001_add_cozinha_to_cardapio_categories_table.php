<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cardapio_categories', function (Blueprint $table) {
            $table->boolean('cozinha')->default(false)->after('name');
        });

        // Mantém o comportamento atual: PORÇÕES e COMIDA exigem preparo na cozinha.
        DB::table('cardapio_categories')->whereIn('name', ['PORÇÕES', 'COMIDA'])->update(['cozinha' => true]);
    }

    public function down(): void
    {
        Schema::table('cardapio_categories', function (Blueprint $table) {
            $table->dropColumn('cozinha');
        });
    }
};
