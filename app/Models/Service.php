<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{    use HasFactory,HasUserActions;

    use HasUserActions;

    protected $guarded=['id'];
    protected $table ='services';

    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'service_tools', 'service_id', 'tool_id');
    }


    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'service_employees', 'service_id', 'employee_id')
        ->withPivot('commission_type', 'commission_value', 'is_immediate_commission');
    }


    public function products()
    {
        return $this->BelongsToMany(Product::class, 'service_products', 'service_id', 'product_id');
    }
}
