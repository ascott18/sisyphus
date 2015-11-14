<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property  int user_department_id This table's primary key.
 * @property  int user_id The user that this department is associated with.
 * @property  string department A department that the user belongs to.
 */
class UserDepartment extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_department_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['department'];
}
