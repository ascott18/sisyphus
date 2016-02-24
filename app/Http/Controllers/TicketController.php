<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use SearchHelper;

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

        TicketComment::create([
            'user_id' => $user_id,
            'ticket_id' => $ticket_id,
            'body' => $comment['body']
        ]);

        $ticket->update(['status' => $status]);

        return response()->json($ticket);
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
}
