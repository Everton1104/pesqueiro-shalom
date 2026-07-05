<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComandaItem extends Model
{
    protected $fillable = [
        'comanda_id', 'cardapio_item_id', 'name', 'category', 'unit_price', 'quantity',
        'observacao', 'preparo', 'status', 'delivered_at',
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'quantity'     => 'integer',
        'preparo'      => 'boolean',
        'delivered_at' => 'datetime',
    ];

    // Itens de cozinha ainda pendentes (alimentam a fila da Cozinha)
    public function scopeCozinhaPendente($query)
    {
        return $query->where('preparo', true)->where('status', 'pendente');
    }

    public function comanda()
    {
        return $this->belongsTo(Comanda::class);
    }

    public function cardapioItem()
    {
        return $this->belongsTo(CardapioItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->unit_price * $this->quantity;
    }

    public function getUnitPriceFormattedAttribute(): string
    {
        return Comanda::money($this->unit_price);
    }

    public function getSubtotalFormattedAttribute(): string
    {
        return Comanda::money($this->subtotal);
    }
}
