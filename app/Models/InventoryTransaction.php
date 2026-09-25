<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'inventory_transactions';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function sourceInventory()
    {
        return $this->belongsTo(Inventory::class, 'source_inventory_id');
    }

    public function destinationInventory()
    {
        return $this->belongsTo(Inventory::class, 'destination_inventory_id');
    }

    public function transactionDetails()
    {
        return $this->hasMany(InventoryTransactionDetail::class);
    }

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function scopeAdjustments($query)
    {
        return $query->where('transaction_type', 'adjustment');
    }

    public function getInventoryAttribute()
    {
        return $this->destinationInventory ?? $this->sourceInventory;
    }

    public function getAdjustmentReasonLabelAttribute(): string
    {
        return match ($this->adjustment_reason) {
            'count_correction' => __('Physical Count Correction'),
            'damage' => __('Damaged Product'),
            'waste' => __('Waste / Expired'),
            'theft' => __('Shrinkage / Theft'),
            'other' => __('Other'),
            default => $this->adjustment_reason ? ucfirst(str_replace('_', ' ', $this->adjustment_reason)) : '—',
        };
    }
}
