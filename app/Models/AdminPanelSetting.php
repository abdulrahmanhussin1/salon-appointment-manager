<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminPanelSetting extends Model
{
    use HasFactory,SoftDeletes,HasUserActions;

    protected $guarded =['id'];
    protected $table = 'admin_panel_settings';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

}
