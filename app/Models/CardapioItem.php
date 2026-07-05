<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardapioItem extends Model
{
    protected $fillable = ['category', 'name', 'description', 'image', 'price', 'sort_order', 'active', 'status'];

    protected $casts = ['price' => 'decimal:2', 'active' => 'boolean'];

    const CATEGORIES = [
        'PORÇÕES',
        'COMIDA',
        'SALGADOS',
        'CERVEJAS',
        'BEBIDAS',
        'BEBIDAS QUENTES',
        'BORBONS',
        'CAIPIRINHAS',
        'DRINKS',
    ];

    const STATUSES = [
        'active'       => ['label' => 'Ativo',         'badge' => 'success'],
        'hidden'       => ['label' => 'Oculto',         'badge' => 'secondary'],
        'unavailable'  => ['label' => 'Indisponível',   'badge' => 'danger'],
        'coming_soon'  => ['label' => 'Em breve',       'badge' => 'warning'],
        'especial'     => ['label' => 'Especial (só comanda/ficha)', 'badge' => 'info'],
    ];

    // Itens exibidos no cardápio público (welcome). 'especial' fica de fora de propósito.
    public function scopeVisible($query)
    {
        return $query->whereIn('status', ['active', 'unavailable', 'coming_soon']);
    }

    // Itens lançáveis numa comanda ou ficha (inclui os especiais, que não saem no cardápio público).
    public function scopeSellable($query)
    {
        return $query->whereIn('status', ['active', 'unavailable', 'especial']);
    }

    public function getPriceFormattedAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    // Item que exige preparo na cozinha (seções marcadas como "cozinha" no cardápio) — usado nas Fichas.
    // Cache estático: carrega as seções de cozinha uma vez por request (evita N+1 em loops de itens).
    public function getRequerPreparoAttribute(): bool
    {
        static $cozinhaCats = null;
        $cozinhaCats ??= CardapioCategory::where('cozinha', true)->pluck('name')->all();

        return in_array($this->category, $cozinhaCats, true);
    }
}
