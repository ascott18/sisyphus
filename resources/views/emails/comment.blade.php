
{{--This is the view that should be sent to whom it may concern when a new comment is made on a ticket. The status needs to be--}}
{{--changed from its number code to its string value (no way exists currently to do that server-side).--}}

<!DOCTYPE html>

<html lang="en">

<div>
    <h3>
        {{$comment->user->first_last_name}} wrote:
    </h3>
    <p style="text-indent: 10px;">
        {{$comment->body }}
    </p>

    <h4>Ticket Status:</h4>
    <div>
        {{ $ticket->status }}
    </div>
    </br>
    </br>
    </br>
    <div>
        View ticket here {{ $link }}
    </div>


</div>
</html>