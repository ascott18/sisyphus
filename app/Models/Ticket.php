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

    private static function getAssignableUsersQuery($department = null){
        $query = User::
            join('role_user', 'role_user.user_id', '=', 'users.user_id')
            ->join('user_departments', 'user_departments.user_id', '=', 'users.user_id');

        if (!$department)
            $query->whereRaw('user_departments.department = tickets.department');
        else
            $query->where('user_departments.department', '=', $department);

        return $query->whereIn('role_user.role_id',
            Permission::where(['name' => 'receive-dept-tickets'])->firstOrFail()
                ->roles()
                ->select('role_id')
                ->toBase()
        );
    }

    public function getAssignableUsers(){
        return static::getAssignableUsersQuery($this->department)->get();
    }

    public function scopeVisible($query, User $user = null){
        if ($user == null)
            $user = \Auth::user();

        if ($user->may('receive-all-tickets')){
            $query->where(function($q) use($user){
                $q->where('tickets.user_id', '=', $user->user_id)
                    ->orWhereNull('tickets.department');
            });
        }
        else{
            $query->where(function($q) use($user){
                $q->where('tickets.user_id', '=', $user->user_id)
                    ->orWhereIn(\DB::raw($user->user_id), static::getAssignableUsersQuery()->select('users.user_id')->toBase());
            });

            //dd($query->toSql());
        }

        return $query;
    }

}
