<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory, HasUserActions;

    protected $guarded = ['id'];

    protected $table = 'services';

    /**
     * Get the service duration parsed in integer minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        $val = $this->duration;

        if (is_numeric($val)) {
            $mins = (int) $val;

            return $mins > 0 ? $mins : 30;
        }

        if (is_string($val) && str_contains($val, ':')) {
            $parts = explode(':', $val);
            $hours = (int) ($parts[0] ?? 0);
            $minutes = (int) ($parts[1] ?? 0);
            $total = ($hours * 60) + $minutes;

            return $total > 0 ? $total : 30;
        }

        return 30;
    }

    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'service_tools', 'service_id', 'tool_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'service_employees', 'service_id', 'employee_id')
            ->withPivot('commission_type', 'commission_value', 'is_immediate_commission');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'service_products', 'service_id', 'product_id');
    }
}
