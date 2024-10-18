<?php

namespace App\Models;

use App\Traits\HasUserActions;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends SpatieRole
{
    use HasUserActions;
}
