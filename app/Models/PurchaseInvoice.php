<?php

namespace App\Models;

use Exception;
use App\Traits\HasUserActions;
use Illuminate\Support\Facades\DB;
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


    public function saveDetails(array $details,$request)
    {
        $inventoryId = $this->branch->inventory()->first()->id;
        if(!$inventoryId)
        {
            throw new Exception('Inventory not found for this branch');
        }
        $transaction = InventoryTransaction::create([
            'transaction_type' => 'purchase',
            'destination_inventory_id' => $inventoryId,
            'total_before_discount' => $request['total_amount'],
            'discount' => $request['invoice_discount'] ?? 0,
            'net_total' => $request['total_amount'] -  $request['invoice_discount'],
        ]);
        foreach ($details as $detail) {
            $this->details()->create([
                'product_id' => $detail['product_id'],
                'supplier_price' => $detail['supplier_price'],
                'quantity' => $detail['quantity'],
                'subtotal' => $detail['subtotal'],
                'discount' => $detail['discount'] ?? 0,
                'notes' => $detail['notes'] ?? null,
            ]);

            $existingProduct = DB::table('inventory_products')
            ->where('inventory_id', $inventoryId)
            ->where('product_id', $detail['product_id'])
            ->first();

            if ($existingProduct) {
                // Update the quantity if the product exists
                DB::table('inventory_products')
                ->where('inventory_id', $inventoryId)
                ->where('product_id', $detail['product_id'])
                ->increment('quantity', $detail['quantity']);
            } else {
                // Insert a new record if the product does not exist
                DB::table('inventory_products')->insert([
                    'inventory_id' =>$inventoryId,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }



            InventoryTransactionDetail::create(['transaction_type' => 'transfer',
                'inventory_transaction_id' => $transaction->id,
                'product_id' => $detail['product_id'],
                'quantity' => $detail['quantity'],
            ]);

            SupplierPrice::create([
                'product_id' => $detail['product_id'],
                'supplier_id' => $this->supplier_id,
                'supplier_price' => $detail['supplier_price'],
                'customer_price'=> $detail['customer_price'],
                 'discount' => $detail['discount'] ,
                 'quantity' => $detail['quantity'],
                'purchase_invoice_id' => $this->id, // Purchase Invoice ID from the current invoice
            ]);
        }


    }
}
