<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
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

    public function getCreate() {
        $this->authorize("all");

        return view('tickets.create');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function postCreate()
    {
        $this->authorize("all");

        $ticket = new Ticket();
        $ticket->title = "";
        $ticket->body = "<h3>New Ticket</h3>";
        $ticket->user_id = Auth::user()->user_id;
        $ticket->save();

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
        $ticket = Ticket::findOrFail($id);

        $this->authorize("view-ticket", $ticket);

        return view('tickets.details', ['ticket' => $ticket]);
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
