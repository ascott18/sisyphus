<?php

namespace App\Http\Controllers;

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
    public static $ticketStatusNames = [
        0 => 'New',
    ];



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
        $options = [["header" => "Header 1", "body" => "body 1"], ["header" => "Header 1", "body" => "body 1"], ["header" => "Header 1", "body" => "body 1"]];
//        $options = $request->input('options');
        return view('tickets.create', ['options' => $options]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSubmitTicket(Request $request)
    {
        $this->authorize("all");

        $ticket = $request->input("ticket");
        $user_id = Auth::user()->user_id;

        Ticket::create([
            'user_id' => $user_id,
            'department' => $ticket['department'],
            'url' => $ticket['url'],
            'body' => $ticket['body'],
            'title' => $ticket['title'],
            'status' => 0
        ]);

        return response()->json($ticket);
    }

    public function postSubmitComment(Request $request) {
        $this->authorize("all");

        $comment = $request->get("comment");
        $ticketId = $request->get("ticketId");
        $user_id = Auth::user()->user_id;

        TicketComment::create([
            'user_id' => $user_id,
            'ticket_id' => $ticketId,
            'body' => $comment['body']
        ]);

        $ticket = $this->initializeTicketComments($ticketId);

        return response()->json($ticket);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getDetails($id)
    {
        $ticket = $this->initializeTicketComments($id);

        return view('tickets.details', ['ticket' => $ticket]);
    }

    private function initializeTicketComments($id) {
        $ticket = Ticket::findOrFail($id);

        $this->authorize("view-ticket", $ticket);
        $ticket->comments = $ticket->comments()->get();

        foreach ($ticket->comments as $comment) {
            $comment->author = User::findOrFail($comment->user_id);
        }

        return $ticket;
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
            $sort = $tableState->sort;

            if($sort->predicate == "name") {
                if ($sort->reverse == 1) {
                    $query = $query->orderBy('users.last_name', "desc");
                    $query = $query->orderBy('users.first_name', "desc");
                } else {
                    $query = $query->orderBy('users.last_name');
                    $query = $query->orderBy('users.first_name');
                }
            } else {
                if ($sort->reverse == 1)
                    $query = $query->orderBy($sort->predicate, "desc");
                else
                    $query = $query->orderBy($sort->predicate);
            }
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

        if(isset($tableState->term_selected) && $tableState->term_selected != "") {
            $query = $query->where('term_id', '=', $tableState->term_selected);
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

        foreach ($tickets as $ticket) {
            $ticket->status_display = static::$ticketStatusNames[$ticket->status];
        }

        return response()->json($tickets);
    }
}
