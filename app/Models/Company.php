<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory,SoftDeletes,HasUserActions;

    protected $guarded =['id'];
    protected $table = 'companies';

    public function adminPanelSettings(): HasOne
    {
        return $this->hasOne(AdminPanelSetting::class);
    }

}
