<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory,HasUserActions;

    protected $guarded = ['id'];
    protected $table = 'products';

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class,'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $latestProduct = self::latest('code')->first();
            $product->code = $latestProduct ? $latestProduct->code + 1 : 100001;
        });
    }
}
