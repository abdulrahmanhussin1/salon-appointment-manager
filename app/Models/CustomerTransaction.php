<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerTransaction extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'customer_transactions';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

}
