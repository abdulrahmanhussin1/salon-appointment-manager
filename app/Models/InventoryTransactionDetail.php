<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryTransactionDetail extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'inventory_transaction_details';

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
}
