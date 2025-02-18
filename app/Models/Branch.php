<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory,HasUserActions;

    protected $guarded=['id'];
    protected $table ='branches';

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'branch_id');
    }
}
