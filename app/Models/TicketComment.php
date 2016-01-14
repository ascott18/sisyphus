<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int ticket_comment_id The primary key of the model.
 * @property int ticket_id The ticket_id of the ticket that this comment was made for.
 * @property int user_id The user_id of the user who created this comment.
 * @property string body The body of the comment.
 */
class TicketComment extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'ticket_comment_id';


    public function user()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'user_id');
    }

    public function ticket()
    {
        return $this->hasOne('App\Models\Ticket', 'ticket_id', 'ticket_id');
    }
}
