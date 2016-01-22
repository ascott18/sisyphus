<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Ticket
 *
 * @property int $user_id The user_id of the user who created this ticket.
 * @property int $ticket_id The primary key of the model.
 * @property int $status The status of the ticket, to be cross referenced with the constants on the Ticket class.
 * @property string $title The title of the ticket
 * @property string $url A url that points to the relevant page that the ticket was created for.
 * @property string $department The department that may be interested in the ticket, or null if n/a.
 * @property string $body The body of the ticket.
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TicketComment[] $comments
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Ticket visible($user = null)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Ticket extends Model
{
    /**
     * The primary key of the model.
     *
     * @var string
     */
    protected $primaryKey = 'ticket_id';

    protected $guarded = array();


    public function user()
    {
        return $this->hasOne('App\Models\User', 'user_id', 'user_id');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\TicketComment', 'ticket_id', 'ticket_id');
    }

    public function scopeVisible($query, User $user = null){
        if ($user == null)
            $user = \Auth::user();


        // TODO: this

//
//        if ($user->may('view-dept-courses'))
//        {
//            $departments = $user->departments()->lists('department');
//            $query = $query->where(function($query) use ($departments, $user) {
//                $query = $query->whereIn('department', $departments);
//                return $query = $query->orWhere('user_id', $user->user_id);
//            });
//        }
//        elseif (!$user->may('view-all-courses'))
//        {
//            $query = $query->where('user_id', $user->user_id);
//        }

        return $query;
    }

}
