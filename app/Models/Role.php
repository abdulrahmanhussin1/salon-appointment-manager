<?php

namespace App\Models;

use App\Traits\HasUserActions;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUserActions;
}
