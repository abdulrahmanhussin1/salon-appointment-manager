<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory,HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'suppliers';

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'reference');
    }
}
