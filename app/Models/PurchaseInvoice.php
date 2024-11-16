<?php

namespace App\Models;

use App\Traits\HasUserActions;
use App\Models\PurchaseInvoiceDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseInvoice extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'purchase_invoices';


    public function details()
    {
        return $this->hasMany(PurchaseInvoiceDetail::class, 'purchase_invoice_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class,'supplier_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->invoice_number = self::generateInvoiceNumber();
        });
    }

    public static function generateInvoiceNumber()
    {
        // Get the latest invoice number
        $lastInvoice = self::latest('invoice_number')->first();

        if (!$lastInvoice) {
            return 1; // Start with 1 if no invoice exists
        }

        return $lastInvoice->invoice_number + 1;
    }


    public function saveDetails(array $details)
    {
        foreach ($details as $detail) {
            $this->details()->create([
                'product_id' => $detail['product_id'],
                'supplier_price' => $detail['supplier_price'],
                'quantity' => $detail['quantity'],
                'subtotal' => $detail['subtotal'],
                'discount' => $detail['discount'] ?? 0,
                'notes' => $detail['notes'] ?? null,
            ]);

            SupplierPrice::create([
                'product_id' => $detail['product_id'],
                'supplier_id' => $this->supplier_id,
                'supplier_price' => $detail['supplier_price'],
                 'discount' => $detail['discount'] ,
                'purchase_invoice_id' => $this->id, // Purchase Invoice ID from the current invoice
            ]);
        }
    }
}
