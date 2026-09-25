<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminPanelSetting extends Model
{
    use HasFactory,HasUserActions,SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'admin_panel_settings';

    protected $casts = [
        'block_insufficient_consumables' => 'boolean',
        'void_time_window_hours' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
