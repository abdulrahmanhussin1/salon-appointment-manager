<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_RETURN = 'return';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_INITIAL_BALANCE = 'initial_balance';
}

