<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comanda extends Model
{
    protected $fillable = [
        'codigo', 'cliente', 'status', 'user_id',
        'observacao', 'payment_method', 'service_fee', 'total', 'closed_at',
    ];

    protected $casts = [
        'service_fee' => 'decimal:2',
        'total'       => 'decimal:2',
        'closed_at'   => 'datetime',
    ];

    const STATUSES = [
        'aberta'    => ['label' => 'Aberta',    'class' => 'bg-success'],
        'fechada'   => ['label' => 'Fechada',   'class' => 'bg-secondary'],
        'cancelada' => ['label' => 'Cancelada', 'class' => 'bg-danger'],
    ];

    const PAYMENT_METHODS = [
        'dinheiro' => 'Dinheiro',
        'pix'      => 'PIX',
        'credito'  => 'Crédito',
        'debito'   => 'Débito',
    ];

    public function items()
    {
        return $this->hasMany(ComandaItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAberta($query)
    {
        return $query->where('status', 'aberta');
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'aberta';
    }

    // Soma dos itens (sem taxa de serviço)
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn($i) => $i->unit_price * $i->quantity);
    }

    public function getSubtotalFormattedAttribute(): string
    {
        return self::money($this->subtotal);
    }

    public function getTotalFormattedAttribute(): string
    {
        // Quando fechada usa o total gravado; quando aberta calcula ao vivo
        $value = $this->status === 'aberta' ? $this->subtotal + (float) $this->service_fee : (float) $this->total;
        return self::money($value);
    }

    public function getServiceFeeFormattedAttribute(): string
    {
        return self::money($this->service_fee);
    }

    public static function money($value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    // Assinatura do estado atual (para detectar mudanças via polling)
    public function liveSignature(): string
    {
        $itens = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        $valor = $itens->reduce(fn($c, $i) => $c + ($i->quantity * (float) $i->unit_price), 0.0);

        return implode('|', [$this->status, $itens->count(), $itens->sum('quantity'), number_format($valor, 2, '.', '')]);
    }
}
