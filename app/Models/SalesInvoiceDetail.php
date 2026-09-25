<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceDetail extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'sales_invoice_details';

    protected $casts = [
        'customer_price' => 'decimal:2',
        'quantity' => 'integer',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'is_immediate_commission' => 'boolean',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function name()
    {
        if (! empty($this->service_id)) {
            return $this->service?->name;
        }

        return $this->product?->name;
    }

    public function provider()
    {
        return $this->belongsTo(Employee::class, 'provider_id');
    }

    public function refundDetails()
    {
        return $this->hasMany(RefundDetail::class, 'sales_invoice_detail_id');
    }

    public function remainingRefundableQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) ($this->refunded_quantity ?? 0));
    }

    public function isFullyRefunded(): bool
    {
        return (int) ($this->refunded_quantity ?? 0) >= (int) $this->quantity;
    }
}
