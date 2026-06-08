<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comanda_pagamentos', function (Blueprint $table) {
            // Detalhamento da divisão que originou o acerto (itens da pessoa, ou "1 de N pessoas")
            $table->text('detalhe')->nullable()->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('comanda_pagamentos', function (Blueprint $table) {
            $table->dropColumn('detalhe');
        });
    }
};
