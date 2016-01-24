<?php

namespace App\Models;

use Zizaco\Entrust\EntrustRole;

/**
 * App\Models\Role
 *
 * @property string $name The identifiable slug of this role.
 * @property string $display_name The human-readable name of this role.
 * @property string $description The human-readable description of this role.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission[] $perms
 * @property integer $id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Role extends EntrustRole
{
    protected $guarded = [];

    /**
     * Many-to-Many relations with the permission model.
     * Wrapper around the absurdly named "perms" that this library provides by default.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->perms();
    }
}