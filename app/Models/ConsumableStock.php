<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableStock extends Model
{
    protected $fillable = [
        'consumable_id',
        'quantity',
        'unit_price',
        'purchase_date',
        'reference'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function (ConsumableStock $stock) {
            $stock->consumable?->increment('current_quantity', (float) $stock->quantity);
        });

        static::updated(function (ConsumableStock $stock) {
            if ($stock->wasChanged('consumable_id')) {
                Consumable::find($stock->getOriginal('consumable_id'))?->decrement('current_quantity', (float) $stock->getOriginal('quantity'));
                $stock->consumable?->increment('current_quantity', (float) $stock->quantity);
                return;
            }

            if ($stock->wasChanged('quantity')) {
                $stock->consumable?->increment(
                    'current_quantity',
                    (float) $stock->quantity - (float) $stock->getOriginal('quantity')
                );
            }
        });

        static::deleted(function (ConsumableStock $stock) {
            $stock->consumable?->decrement('current_quantity', (float) $stock->quantity);
        });
    }

    public function consumable()
    {
        return $this->belongsTo(Consumable::class);
    }
}
