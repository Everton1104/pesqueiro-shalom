<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ficha extends Model
{
    protected $fillable = [
        'codigo', 'cliente', 'status', 'payment_method', 'total', 'user_id', 'paid_at', 'concluded_at',
    ];

    protected $casts = [
        'total'        => 'decimal:2',
        'paid_at'      => 'datetime',
        'concluded_at' => 'datetime',
    ];

    // Categorias do cardápio que exigem preparo (vão para a Cozinha)
    const COZINHA_CATEGORIES = ['PORÇÕES', 'COMIDA'];

    const STATUSES = [
        'paga'      => ['label' => 'Paga',      'class' => 'bg-success'],
        'concluida' => ['label' => 'Concluída', 'class' => 'bg-secondary'],
        'cancelada' => ['label' => 'Cancelada', 'class' => 'bg-danger'],
    ];

    public function items()
    {
        return $this->hasMany(FichaItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePaga($query)
    {
        return $query->where('status', 'paga');
    }

    public function balcaoItems()
    {
        $itens = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        return $itens->where('destino', 'balcao')->values();
    }

    public function cozinhaItems()
    {
        $itens = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        return $itens->where('destino', 'cozinha')->values();
    }

    // Itens que a cozinha realmente prepara (porções/comida)
    public function preparoItems()
    {
        return $this->cozinhaItems()->where('preparo', true)->values();
    }

    // Itens que vão junto com a porção (ex.: bebida marcada para a cozinha)
    public function acompanhaItems()
    {
        return $this->cozinhaItems()->where('preparo', false)->values();
    }

    public function getTotalFormattedAttribute(): string
    {
        return Comanda::money($this->total);
    }

    public function getStatusBadgeAttribute(): array
    {
        return self::STATUSES[$this->status] ?? ['label' => $this->status, 'class' => 'bg-light'];
    }

    public function getPaymentLabelAttribute(): string
    {
        return Comanda::paymentLabel($this->payment_method);
    }

    // Recalcula o status da ficha a partir dos itens (concluída quando tudo foi entregue)
    public function recalcStatus(): void
    {
        if ($this->status === 'cancelada') {
            return;
        }

        $itens = $this->items()->get();
        $tudoEntregue = $itens->isNotEmpty() && $itens->every(fn($i) => $i->status === 'entregue');

        if ($tudoEntregue && $this->status !== 'concluida') {
            $this->update(['status' => 'concluida', 'concluded_at' => now()]);
        } elseif (!$tudoEntregue && $this->status === 'concluida') {
            $this->update(['status' => 'paga', 'concluded_at' => null]);
        }
    }

    public static function gerarCodigo(): string
    {
        do {
            $codigo = strtoupper(Str::random(6));
        } while (self::where('codigo', $codigo)->exists());

        return $codigo;
    }

    // Assinatura do estado (para polling de mudanças)
    public function liveSignature(): string
    {
        $itens = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        return implode('|', [
            $this->status,
            $itens->where('destino', 'cozinha')->where('status', 'pendente')->count(),
            $itens->where('destino', 'cozinha')->where('status', 'entregue')->count(),
        ]);
    }
}
