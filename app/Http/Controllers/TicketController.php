<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Ticket;
use App\Models\TicketComment;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests;
use URL;
use SearchHelper;
use DB;
use Mail;

class TicketController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $this->authorize("all");

        return view('tickets.index');
    }

    public function getCreate(Request $request) {
        $this->authorize("all");

        $url = $request->input('url');
        $department = $request->input('department');
        $title = $request->input('title');

        $ticket = new Ticket();
        if ($title != null) {
            $ticket->title = $title;
        }
        $ticket->url = $url;
        $ticket->department = $department;
        $ticket->user_id = Auth::user()->user_id;

        return view('tickets.create', ['ticket' => $ticket]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function postSubmitTicket(Request $request)
    {
        $this->authorize("all");
        $ticket = $request->get("ticket");

        $ticket = Ticket::create([
            'user_id' => $ticket['user_id'],
            'department' => $ticket['department'],
            'url' => $ticket['url'],
            'body' => $ticket['body'],
            'title' => $ticket['title'],
            'status' => 0
        ]);

        return response()->json(['ticket' => $ticket]);
    }

    public function postSubmitComment(Request $request) {

        $ticket_id = $request->get("ticket_id");

        $ticket = Ticket::with(['comments.user', 'user'])->findOrFail($ticket_id);
        $this->authorize("view-ticket", $ticket);

        $comment = $request->get("comment");
        $status = $request->get("status");
        $user_id = Auth::user()->user_id;

        $comment = TicketComment::create([
            'user_id' => $user_id,
            'ticket_id' => $ticket_id,
            'body' => $comment['body']
        ]);

        $ticket->update(['status' => $status]);
        $ticket = Ticket::with(['comments.user', 'user'])->findOrFail($ticket_id);

        return ['success' => true, 'ticket' => $ticket, 'comment' => $comment];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getDetails($ticket_id)
    {
        $ticket = Ticket::with(['comments.user', 'user'])->findOrFail($ticket_id);

        $this->authorize("view-ticket", $ticket);


        $department = $ticket->department;

        if ($department == null) {
            $ticket['assignedToDisplay'] = "Bookstore Staff";
        }
        else {

            $eligibleUsers = $ticket->getAssignableUsers();

            if (count($eligibleUsers) == 0)
                $ticket['assignedToDisplay'] = "Bookstore Staff";
            else
                $ticket['assignedToDisplay'] = join(', ', $eligibleUsers->pluck('first_last_name')->toArray());
        }


        return view('tickets.details', ['ticket' => $ticket]);
    }


    /**
     * Build the search query for the tickets controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param $tableState
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTicketSearchQuery($tableState, $query) {
        $predicateObject = [];
        if(isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if(isset($predicateObject->title))
            $query = $query->where('tickets.title', 'LIKE', '%'.$predicateObject->title.'%');
        if(isset($predicateObject->name)) {
            SearchHelper::professorSearchQuery($query, $predicateObject->name);
        }

        return $query;
    }

    /**
     * Build the sort query for the tickets controller
     *
     * @param \Illuminate\Database\Eloquent\Builder
     * @param \Illuminate\Http\Request $tableState
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTicketSortQuery($tableState, $query) {
        if(isset($tableState->sort->predicate)) {
            if(isset($tableState->sort->predicate)) {
                $sorts = [
                    'title' => [
                        'tickets.title', '',
                    ],
                    'name' => [
                        'users.last_name', '',
                        'users.first_name', '',
                    ]
                ];

                SearchHelper::buildSortQuery($query, $tableState->sort, $sorts);
            }
            return $query;
        }

        return $query;
    }



    /**
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTicketList(Request $request)
    {
        $tableState = json_decode($request->input('table_state'));

        $this->authorize("all");

        $query = Ticket::visible($request->user());

        if(isset($tableState->statusSelected) && $tableState->statusSelected) {
            $query = $query->where('status', '=', $tableState->statusSelected->key);
        }

        if((isset($tableState->sort->predicate) && $tableState->sort->predicate == "name")
            || isset($tableState->search->predicateObject->name) ) { // only join when we actually need it

            $query->join('users','users.user_id', '=', 'tickets.user_id');

        }

        // TODO: nathan do this
        $query = $this->buildTicketSearchQuery($tableState, $query);
        $query = $this->buildTicketSortQuery($tableState, $query);

        $query = $query->with("user");

        $tickets = $query->paginate(10);

        return response()->json($tickets);
    }


    public function postSendNewCommentEmail(Request $request)
    {
        $currentUser = $request->user();
        $ticket = Ticket::with(['comments.user', 'user'])->findOrFail($request->input('ticket_id'));
        $comment = TicketComment::with(['user'])->findOrFail($request->input('ticket_comment_id'));

        $this->authorize("view-ticket", $ticket);

        $numRequested = 0;
        $numSent = 0;
        $url = action('TicketController@getDetails', ['ticket_id' => $ticket->ticket_id]);

        if ($ticket->department) {

            $recipients = $ticket->getAssignableUsers();
            $ticket['numRecipients'] = count($recipients);

            if (count($recipients) != 0)
                $ticket['assignedToDisplay'] = join(', ', $recipients->pluck('first_last_name')->toArray());


            foreach ($recipients as $recipient)
            {
                $numRequested++;

                if ($recipient && $recipient->email)
                {
                    $numSent++;
                    Mail::queue('emails.comment', ['ticket' => $ticket,
                                                    'comment' => $comment,
                                                    'link' => $url],

                        function ($m) use ($recipient, $ticket, $currentUser, $comment) {
                        $email = $recipient->email;
                        $sender = $recipient->first_name . ' ' . $recipient->last_name;
                        $m->from($currentUser->email, "$currentUser->first_name $currentUser->last_name");
                        $m->to($email, $sender);
                        $m->subject($sender . " commented on ticket: " . $ticket->title);
                    });
                }
            }

        }


        return ['success' => true, 'requested' => $numRequested, 'sent' => $numSent, 'recipientIds' => $recipients, 'url' => $url];
    }

    public function postSendNewTicketEmail(Request $request)
    {
        $currentUser = $request->user();
        $ticket = Ticket::findOrFail($request->input('ticket_id'));
        $this->authorize("view-ticket", $ticket);

        $numRequested = 0;
        $numSent = 0;

        $url = action('TicketController@getDetails', ['ticket_id' => $ticket->ticket_id]);

        if ($ticket->department) {
            $recipients = $ticket->getAssignableUsers();

            foreach ($recipients as $recipient)
            {
                $numRequested++;

                if ($recipient && $recipient->email)
                {
                    $numSent++;
                    Mail::queue('emails.ticket', ['ticket' => $ticket,
                                                            'link' => $url],
                        function ($m) use ($recipient, $ticket, $currentUser) {
                        $email = $recipient->email;
                        $m->from($currentUser->email, "$currentUser->first_name $currentUser->last_name");
                        $m->to($email, $recipient->first_name . ' ' . $recipient->last_name);
                        $m->subject("Ticket created: " . $ticket->title);
                    });
                }
            }
        }


        return ['success' => true, 'requested' => $numRequested, 'sent' => $numSent, 'recipientIds' => $recipients];
    }
}
