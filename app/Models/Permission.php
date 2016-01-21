<?php

namespace App\Models;

use Zizaco\Entrust\EntrustPermission;

/**
 * App\Models\Permission
 *
 * @property string $name The identifiable slug of this permission.
 * @property string $display_name The human-readable name of this permission.
 * @property string $description The human-readable description of this permission.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Permission extends EntrustPermission
{
}