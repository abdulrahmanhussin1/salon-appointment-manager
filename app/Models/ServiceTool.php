<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTool extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table ='service_tools';

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_tools');
    }
}
