<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string subject The subject line of the email message.
 * @property string body The body of the email message.
 * @property Carbon last_sent The date on which the message was last sent.
 * @property int owner_user_id The user_id of the user that created this message.
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
    protected $fillable = ['subject', 'body', 'last_sent'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'last_sent'];


    public function owner()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'owner_user_id');
    }
}
