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
    ];

    public function scopeVisible($query)
    {
        return $query->whereIn('status', ['active', 'unavailable', 'coming_soon']);
    }

    public function getPriceFormattedAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    // Item que exige preparo na cozinha (categorias PORÇÕES / COMIDA) — usado nas Fichas
    public function getRequerPreparoAttribute(): bool
    {
        return in_array($this->category, Ficha::COZINHA_CATEGORIES, true);
    }
}
