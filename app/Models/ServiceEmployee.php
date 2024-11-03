<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceEmployee extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table ='service_employees';

    public function services()
    {
        return $this->belongsToMany(Service::class,'service_employees');
    }
}
