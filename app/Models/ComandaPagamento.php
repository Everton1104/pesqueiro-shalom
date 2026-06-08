<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComandaPagamento extends Model
{
    protected $table = 'comanda_pagamentos';

    protected $fillable = [
        'comanda_id', 'descricao', 'detalhe', 'valor', 'payment_method', 'is_final', 'user_id',
    ];

    protected $casts = [
        'valor'    => 'decimal:2',
        'is_final' => 'boolean',
        'detalhe'  => 'array',
    ];

    // Linhas legíveis do detalhamento da divisão que originou o acerto
    public function getDetalheLinhasAttribute(): array
    {
        $d = $this->detalhe;
        if (!is_array($d)) {
            return [];
        }

        if (($d['tipo'] ?? null) === 'pessoa') {
            $linhas = array_map(
                fn($it) => ($it['qtd'] ?? '') . 'x ' . ($it['nome'] ?? ''),
                $d['itens'] ?? []
            );
            if (!empty($d['desconto'])) {
                $linhas[] = '− ' . Comanda::money($d['desconto']) . ' crédito já pago';
            }
            return $linhas;
        }

        if (($d['tipo'] ?? null) === 'igual') {
            return array_filter([$d['fracao'] ?? 'Divisão igual']);
        }

        return [];
    }

    public function comanda()
    {
        return $this->belongsTo(Comanda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getValorFormattedAttribute(): string
    {
        return Comanda::money($this->valor);
    }

    public function getMethodLabelAttribute(): string
    {
        return Comanda::PAYMENT_METHODS[$this->payment_method] ?? '—';
    }
}
