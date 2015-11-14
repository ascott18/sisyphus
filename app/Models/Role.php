<?php

namespace App\Models;

use Zizaco\Entrust\EntrustRole;

/**
 * @property  string name The identifiable slug of this role.
 * @property  string display_name The human-readable name of this role.
 * @property  string description The human-readable description of this role.
 */
class Role extends EntrustRole
{
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