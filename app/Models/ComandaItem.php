<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComandaItem extends Model
{
    protected $fillable = [
        'comanda_id', 'cardapio_item_id', 'name', 'unit_price', 'quantity', 'observacao',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity'   => 'integer',
    ];

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
