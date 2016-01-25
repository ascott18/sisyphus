<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Zizaco\Entrust\Traits\EntrustUserTrait;


/**
 * App\Models\User
 *
 * @property int $user_id
 * @property string $net_id The user's EWU NetID.
 * @property string $email The user's email address.
 * @property string $first_name
 * @property string $last_name
 * @property string $ewu_id The user's 8-digit EWU ID.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Course[] $courses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserDepartment[] $departments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ticket[] $tickets
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Message[] $messages
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $last_first_name
 */
class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract
{
    use Authenticatable, Authorizable, EntrustUserTrait {
        EntrustUserTrait::can as may;
        Authorizable::can insteadof EntrustUserTrait;
    }

    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'email'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['last_first_name'];



    public function getFirstLastNameAttribute()
    {
        return "$this->first_name $this->last_name";
    }
    public function getLastFirstNameAttribute()
    {
        return "$this->last_name, $this->first_name";
    }

    public function courses()
    {
        return $this->hasMany('App\Models\Course', 'user_id', 'user_id');
    }

    public function departments()
    {
        return $this->hasMany('App\Models\UserDepartment', 'user_id', 'user_id');
    }

    /**
     * Wrapper to get only the user's first role, which is all we ever use.
     */
    public function role()
    {
        return $this->roles()->first();
    }

    public function tickets()
    {
        return $this->hasMany('App\Models\Ticket', 'user_id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany('App\Models\Message', 'owner_user_id', 'user_id');
    }

}
