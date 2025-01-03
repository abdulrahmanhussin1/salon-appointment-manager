<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'inventories';

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventoryProducts()
    {
        return $this->hasMany(InventoryProduct::class);
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'reference');
    }

}
