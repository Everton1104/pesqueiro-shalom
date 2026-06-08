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

    // Forma derivada (não selecionável): comanda quitada com acertos em formas diferentes
    const PAYMENT_MISTO = 'misto';

    // Rótulo de uma forma de pagamento, incluindo "Misto"
    public static function paymentLabel(?string $key): string
    {
        if ($key === self::PAYMENT_MISTO) {
            return 'Misto';
        }

        return self::PAYMENT_METHODS[$key] ?? '—';
    }

    public function items()
    {
        return $this->hasMany(ComandaItem::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(ComandaPagamento::class)->latest();
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

    // Total já acertado em pagamentos parciais
    public function getPagoAttribute(): float
    {
        $pgs = $this->relationLoaded('pagamentos') ? $this->pagamentos : $this->pagamentos()->get();
        return (float) $pgs->sum('valor');
    }

    public function getPagoFormattedAttribute(): string
    {
        return self::money($this->pago);
    }

    // Quanto ainda falta acertar (base: subtotal + taxa de serviço − já pago)
    public function getRestanteAttribute(): float
    {
        $base = $this->status === 'aberta'
            ? $this->subtotal + (float) $this->service_fee
            : (float) $this->total;

        return round(max(0, $base - $this->pago), 2);
    }

    public function getRestanteFormattedAttribute(): string
    {
        return self::money($this->restante);
    }

    public function getTemPagamentosParciaisAttribute(): bool
    {
        return $this->pago > 0;
    }

    // Quantidades já acertadas por item (comanda_item_id => qtd), vindas do detalhe dos pagamentos "por pessoa"
    public function itemPaidQuantities(): array
    {
        $pgs = $this->relationLoaded('pagamentos') ? $this->pagamentos : $this->pagamentos()->get();
        $map = [];

        foreach ($pgs as $pg) {
            $det = $pg->detalhe;
            if (!is_array($det) || ($det['tipo'] ?? null) !== 'pessoa') {
                continue;
            }
            foreach ($det['itens'] ?? [] as $it) {
                $id = $it['id'] ?? null;
                if ($id) {
                    $map[$id] = ($map[$id] ?? 0) + (int) ($it['qtd'] ?? 0);
                }
            }
        }

        return $map;
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
        $pgs   = $this->relationLoaded('pagamentos') ? $this->pagamentos : $this->pagamentos()->get();

        return implode('|', [
            $this->status, $itens->count(), $itens->sum('quantity'), number_format($valor, 2, '.', ''),
            $pgs->count(), number_format((float) $pgs->sum('valor'), 2, '.', ''),
        ]);
    }
}
