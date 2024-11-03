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
        return $this->BelongsToMany(ServiceTool::class);
    }

    public function employees()
    {
        return $this->BelongsToMany(ServiceEmployee::class);
    }

    public function products()
    {
        return $this->BelongsToMany(ServiceProduct::class);
    }
}
