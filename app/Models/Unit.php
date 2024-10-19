<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasUserActions;

    protected $guarded =['id'];
    protected $table = 'units';
}
