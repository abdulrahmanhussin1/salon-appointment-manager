<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory,HasUserActions;

    protected $guarded=['id'];
    protected $table = 'employees';

    public function employeeLevel()
    {
        return $this->belongsTo(EmployeeLevel::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class,'service_employees');
    }

}
