
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