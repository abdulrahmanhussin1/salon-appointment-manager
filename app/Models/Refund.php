<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'refunds';

    protected $casts = [
        'refund_date' => 'date',
        'total_refund_amount' => 'decimal:2',
        'tax_refund_amount' => 'decimal:2',
        'commission_reversed_amount' => 'decimal:2',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function refundDetails()
    {
        return $this->hasMany(RefundDetail::class, 'refund_id');
    }

    public function customerTransaction()
    {
        return $this->morphOne(CustomerTransaction::class, 'reference');
    }

    /**
     * Scope query to a specific branch.
     */
    public function scopeBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get human-readable label for refund method.
     */
    public function getRefundMethodLabelAttribute(): string
    {
        return match ($this->refund_method) {
            'cash' => __('Cash'),
            'deposit' => __('Customer Deposit Credit'),
            'card' => __('Card / Electronic'),
            'bank_transfer' => __('Bank Transfer'),
            default => ucfirst(str_replace('_', ' ', $this->refund_method ?? 'cash')),
        };
    }

    /**
     * Generate next sequential refund number.
     */
    public static function generateRefundNumber(): string
    {
        $prefix = 'REF-'.date('Ymd').'-';
        $latest = self::where('refund_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($latest) {
            $lastSeq = (int) substr($latest->refund_number, strlen($prefix));
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix.$seq;
    }
}
