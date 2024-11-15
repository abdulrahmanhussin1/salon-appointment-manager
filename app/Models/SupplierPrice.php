<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPrice extends Model
{
   // use HasFactory;

   protected $guarded = ['id'];

   protected $table = 'supplier_prices';




    public static function createOrUpdatePrice($productId, $supplierId, $supplierPrice, $invoiceId)
    {
        $supplierPriceRecord = self::updateOrCreate(
            [
                'product_id' => $productId,
                'supplier_id' => $supplierId,
            ],
            [
                'supplier_price' => $supplierPrice,
                'invoice_id' => $invoiceId,
            ]
        );

        return $supplierPriceRecord;
    }
}


