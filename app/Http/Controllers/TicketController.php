<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function postCreate()
    {
        //
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
