<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cardapio_items', function (Blueprint $table) {
            // 'active' vira 'status': active | hidden | unavailable | coming_soon
            $table->string('status')->default('active')->after('active');
        });

        // Migra o valor antigo
        DB::table('cardapio_items')->where('active', true)->update(['status' => 'active']);
        DB::table('cardapio_items')->where('active', false)->update(['status' => 'hidden']);
    }

    public function down(): void
    {
        Schema::table('cardapio_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
