<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundDetail extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'refund_details';

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'commission_reversed' => 'decimal:2',
        'inventory_restored' => 'boolean',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class, 'refund_id');
    }

    public function salesInvoiceDetail()
    {
        return $this->belongsTo(SalesInvoiceDetail::class, 'sales_invoice_detail_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function provider()
    {
        return $this->belongsTo(Employee::class, 'provider_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getItemNameAttribute(): string
    {
        if ($this->service_id && $this->service) {
            return $this->service->name;
        }

        if ($this->product_id && $this->product) {
            return $this->product->name;
        }

        return __('Item');
    }
}
