<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FichaItem extends Model
{
    protected $fillable = [
        'ficha_id', 'cardapio_item_id', 'name', 'category',
        'unit_price', 'quantity', 'observacao', 'destino', 'preparo', 'status', 'delivered_at',
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'quantity'     => 'integer',
        'preparo'      => 'boolean',
        'delivered_at' => 'datetime',
    ];

    public function ficha()
    {
        return $this->belongsTo(Ficha::class);
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
