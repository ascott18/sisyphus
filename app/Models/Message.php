<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string subject
 * @property string body
 * @property int owner_user_id
 */
class Message extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'message_id';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['subject', 'body'];


    public function owner()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'owner_user_id');
    }
}
