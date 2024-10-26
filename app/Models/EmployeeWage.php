<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeWage extends Model
{
    use HasFactory,HasUserActions;

    protected $guarded=['id'];
    protected $table = 'employee_wages';
    
}
