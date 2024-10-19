<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasUserActions;

    protected $guarded = ['id'];
    protected $table ='suppliers';
}
