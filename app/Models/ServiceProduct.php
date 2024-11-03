<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProduct extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table ='service_products';
    public function services()
    {
        return $this->belongsToMany(Service::class,'services_products',);
    }

}
