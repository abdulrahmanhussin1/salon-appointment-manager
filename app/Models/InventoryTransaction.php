<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        return $this->belongsTo(Inventory::class,'source_inventory_id');
    }

    public function destinationInventory()
    {
        return $this->belongsTo(Inventory::class,'destination_inventory_id');
    }

    public function transactionDetails()
    {
        return $this->hasMany(InventoryTransactionDetail::class);
    }
    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

}
