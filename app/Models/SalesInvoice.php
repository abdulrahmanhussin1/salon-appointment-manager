<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesInvoice extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'sales_invoices';

    protected $casts = [
        'voided_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function salesInvoiceDetails()
    {
        return $this->hasMany(SalesInvoiceDetail::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'sales_invoice_id');
    }

    public function remainingRefundableAmount(): float
    {
        return max(0.0, (float) $this->net_total - (float) ($this->total_refunded ?? 0));
    }

    public function isRefundable(): bool
    {
        return $this->status === 'active' && $this->refund_status !== 'full';
    }

    /**
     * Void the invoice and reverse its effects.
     */
    public function void(string $reason, ?int $userId = null): void
    {
        if ($this->status === 'voided') {
            throw new \Exception('Invoice is already voided.');
        }

        if ($this->refunds()->count() > 0) {
            throw new \Exception('Invoice cannot be voided because it has associated refunds. Process refunds for returns instead.');
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw new \Exception('A void reason is required.');
        }

        // Check configurable time window (default: 24 hours)
        $windowHours = (int) (AdminPanelSetting::value('void_time_window_hours') ?? 24);
        if ($this->created_at && now()->diffInHours($this->created_at) > $windowHours) {
            throw new \Exception("Void window has expired. Invoices can only be voided within {$windowHours} hours of creation.");
        }

        DB::transaction(function () use ($reason, $userId) {
            // 1. If invoice was active, reverse inventory deductions
            if ($this->status === 'active') {
                $this->reverseInventoryDeductions();
                $this->reverseCustomerLastService();
            }

            // 2. Reverse customer deposit usage if deposits were used
            $this->reverseCustomerDepositUsage();

            // 3. Mark invoice as voided with audit information
            $this->update([
                'status' => 'voided',
                'voided_by' => $userId ?? auth()->id(),
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);
        });
    }

    /**
     * Reverse inventory deductions (retail products and service consumables) in the invoice's branch.
     */
    public function reverseInventoryDeductions(): void
    {
        // 1. Restore retail products
        $productDetails = $this->salesInvoiceDetails()
            ->whereNotNull('product_id')
            ->get();

        foreach ($productDetails as $detail) {
            $invProduct = InventoryProduct::where('product_id', $detail->product_id)
                ->whereHas('inventory', fn ($q) => $q->where('branch_id', $this->branch_id))
                ->lockForUpdate()
                ->first();

            if ($invProduct) {
                $invProduct->quantity += $detail->quantity;
                $invProduct->save();
            } else {
                $inventory = Inventory::where('branch_id', $this->branch_id)->first();
                if ($inventory) {
                    InventoryProduct::create([
                        'inventory_id' => $inventory->id,
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                    ]);
                }
            }
        }

        // 2. Restore service consumables
        $serviceDetails = $this->salesInvoiceDetails()
            ->whereNotNull('service_id')
            ->get();

        foreach ($serviceDetails as $detail) {
            $service = Service::with('products')->find($detail->service_id);
            if (! $service) {
                continue;
            }

            foreach ($service->products as $product) {
                $qtyPerService = $product->pivot->product_quantity ?? 1;
                $totalConsumableQty = (int) $qtyPerService * (int) $detail->quantity;

                if ($totalConsumableQty <= 0) {
                    continue;
                }

                $invProduct = InventoryProduct::where('product_id', $product->id)
                    ->whereHas('inventory', fn ($q) => $q->where('branch_id', $this->branch_id))
                    ->lockForUpdate()
                    ->first();

                if ($invProduct) {
                    $invProduct->quantity += $totalConsumableQty;
                    $invProduct->save();
                } else {
                    $inventory = Inventory::where('branch_id', $this->branch_id)->first();
                    if ($inventory) {
                        InventoryProduct::create([
                            'inventory_id' => $inventory->id,
                            'product_id' => $product->id,
                            'quantity' => $totalConsumableQty,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Restore customer deposit usage back to the source deposit transaction.
     */
    public function reverseCustomerDepositUsage(): void
    {
        $usages = CustomerTransaction::where('reference_type', 'invoice')
            ->where('reference_id', $this->id)
            ->get();

        foreach ($usages as $usage) {
            if ($usage->used_in_transaction_id) {
                $sourceDeposit = CustomerTransaction::find($usage->used_in_transaction_id);
                if ($sourceDeposit) {
                    if ($sourceDeposit->status === 'used') {
                        $sourceDeposit->status = 'available';
                    } else {
                        $sourceDeposit->amount += abs($usage->amount);
                    }
                    $sourceDeposit->save();
                }
            }
            $usage->delete();
        }
    }

    /**
     * Recompute customer's last_service if this invoice established it.
     */
    public function reverseCustomerLastService(): void
    {
        $customer = Customer::where('id', $this->customer_id)->lockForUpdate()->first();
        if ($customer && $customer->last_service && $customer->last_service >= $this->invoice_date) {
            $latestInvoiceDate = self::where('customer_id', $this->customer_id)
                ->where('id', '!=', $this->id)
                ->where('status', 'active')
                ->whereHas('salesInvoiceDetails', fn ($q) => $q->whereNotNull('service_id'))
                ->max('invoice_date');

            $customer->update(['last_service' => $latestInvoiceDate]);
        }
    }
}
