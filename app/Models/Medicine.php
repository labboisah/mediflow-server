<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    protected $guarded = [];

    public function medicineType() {
        return $this->belongsTo(MedicineType::class);
    }

    public function batches() {
        return $this->hasMany(MedicineBatch::class);
    }

    public function availableBatches()
    {
        return $this->batches()
            ->where('quantity_remaining', '>', 0)
            ->where('expiry_date', '>=', now()->toDateString());
    }

    public function availableQuantity(): int
    {
        if ($this->relationLoaded('batches')) {
            return (int) $this->batches
                ->where('quantity_remaining', '>', 0)
                ->where('expiry_date', '>=', now()->toDateString())
                ->sum('quantity_remaining');
        }

        return (int) $this->availableBatches()->sum('quantity_remaining');
    }

    public function latestSellingPrice(): float
    {
        $batch = $this->relationLoaded('batches')
            ? $this->batches
                ->where('quantity_remaining', '>', 0)
                ->where('expiry_date', '>=', now()->toDateString())
                ->sortBy('expiry_date')
                ->first()
            : $this->availableBatches()->orderBy('expiry_date')->orderBy('created_at')->first();

        return (float) ($batch?->selling_price ?? 0);
    }

    public function availabilityLabel(): string
    {
        $quantity = $this->availableQuantity();

        return $quantity > 0 ? "Available ({$quantity})" : 'Not available';
    }

    public function displayName(): string
    {
        return collect([
            $this->name,
            $this->generic_name ? "Generic: {$this->generic_name}" : null,
            $this->manufacturer ? "Company: {$this->manufacturer}" : null,
            $this->availabilityLabel(),
        ])->filter()->implode(' | ');
    }

}
