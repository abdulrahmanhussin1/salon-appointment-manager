<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{    use HasFactory,HasUserActions;

    use HasUserActions;

    protected $guarded=['id'];
}
