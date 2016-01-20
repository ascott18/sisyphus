<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Message
 *
 * @property string $subject The subject line of the email message.
 * @property string $body The body of the email message.
 * @property \Carbon\Carbon $last_sent The date on which the message was last sent.
 * @property integer $owner_user_id The user_id of the user that created this message.
 * @property-read \App\Models\User $owner
 * @property integer $message_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
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
