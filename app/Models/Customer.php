<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'customers';

    public function inventoryTransactions()
    {
        return $this->morphMany(InventoryTransaction::class, 'reference');
    }

    public function customerTransactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }
    
    public function getAvailableDepositAmount()
    {
        return CustomerTransaction::getAvailableDeposits($this->id)->sum('amount');
    }


}
