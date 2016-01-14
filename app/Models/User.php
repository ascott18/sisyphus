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
 * @property  int user_id
 * @property  string net_id The user's EWU NetID.
 * @property  string email The user's email address.
 * @property  string first_name
 * @property  string last_name
 * @property  string ewu_id The user's 8-digit EWU ID.
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
    protected $fillable = [];


    public function getFirstLast()
    {
        return "$this->first_name $this->last_name";
    }

    public function getLastFirst()
    {
        return "$this->last_name, $this->first_name";
    }

    public function courses()
    {
        return $this->hasMany('App\Models\Course', 'user_id', 'user_id');
    }

    /**
     * Gets the users courses for which orders are currently open.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function currentCourses()
    {
        $currentTermsIds = Term::currentTerms()->select('term_id')->get()->values();

        return $this->courses()->whereIn('term_id', $currentTermsIds);
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

}
