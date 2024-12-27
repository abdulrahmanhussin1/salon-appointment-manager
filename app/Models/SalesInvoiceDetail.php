<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesInvoiceDetail extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'sales_invoice_details';

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

    public function provider()
    {
        return $this->belongsTo(Employee::class);
    }



    public function name()
    {
        if (!empty($this->service_id)) {
            return $this->service?->name;
        }

        return $this->product?->name;
    }




}
