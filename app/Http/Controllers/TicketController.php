<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

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
        //$options = [["header" => "Header 1", "body" => "body 1"], ["header" => "Header 1", "body" => "body 1"], ["header" => "Header 1", "body" => "body 1"]];
        $options = $request->input('options');
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

        $ticket = $request->get("ticket");
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
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTicketList(Request $request)
    {
        $this->authorize("all");

        $query = Ticket::visible($request->user());

        // TODO: nathan do this
//        $query = $this->buildSearchQuery($request, $query);
//        $query = $this->buildSortQuery($request, $query);
        $query = $query->with("user");

        $tickets = $query->paginate(10);

        foreach ($tickets as $ticket) {
            $ticket->status_display = static::$ticketStatusNames[$ticket->status];
        }

        return response()->json($tickets);
    }
}
