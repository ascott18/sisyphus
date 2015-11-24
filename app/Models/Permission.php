<?php

namespace App\Models;

use Zizaco\Entrust\EntrustPermission;

/**
 * @property  string name The identifiable slug of this permission.
 * @property  string display_name The human-readable name of this permission.
 * @property  string description The human-readable description of this permission.
 */
class Permission extends EntrustPermission
{
}