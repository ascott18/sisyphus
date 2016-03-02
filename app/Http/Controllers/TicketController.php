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
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTicketSearchQuery($tableState, $query) {
        $predicateObject = [];
        if (isset($tableState->search->predicateObject))
            $predicateObject = $tableState->search->predicateObject; // initialize predicate object

        if (isset($predicateObject->title) && $predicateObject->title != '')
            $query = $query->where('tickets.title', 'LIKE', '%'.$predicateObject->title.'%');
        if (isset($predicateObject->name) && $predicateObject->name != '') {
            SearchHelper::professorSearchQuery($query, $predicateObject->name);

        return $query;
    }

    /**
     * Build the sort query for the tickets controller
     *
     * @param object $tableState
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildTicketSortQuery($tableState, $query) {
        if (isset($tableState->sort->predicate)) {
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
        $this->authorize("all");

        $tableState = json_decode($request->input('table_state'));

        $query = Ticket::visible();

        if (isset($tableState->statusSelected->key)) {
            $query = $query->where('status', '=', $tableState->statusSelected->key);
        }

        if ((isset($tableState->sort->predicate) && $tableState->sort->predicate == "name")
            || isset($tableState->search->predicateObject->name) ) { // only join when we actually need it

            $query->join('users','users.user_id', '=', 'tickets.user_id');

        }

        $query = $this->buildTicketSearchQuery($tableState, $query);
        $query = $this->buildTicketSortQuery($tableState, $query);

        $query = $query->with([
            'user' => function($query){
                return $query->select('user_id', 'last_name', 'first_name');
            }
        ]);

        $tickets = $query->paginate(10);

        return $tickets;
    }
}
