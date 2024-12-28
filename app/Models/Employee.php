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
        return $this->belongsToMany(Service::class,'service_employees', 'employee_id','service_id')
            ->withPivot(['commission_type', 'commission_value', 'is_immediate_commission']);
    }


    public function salesInvoiceDetails()
    {
        return $this->hasMany(SalesInvoiceDetail::class,'provider_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->hasOne(User::class,'employee_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($employee) {
            EmployeeWage::create([
                'employee_id' => $employee->id, // This will now have a valid ID
            ]);
        });
    }


}
