

<!DOCTYPE html>

<html lang="en">

<div>
    <h3>
        {{$ticket->title}}
    </h3>
    <p>
        {!!html_entity_decode($ticket->body)!!}
    </p>

    <h4>Created By</h4>
    <div>
        {{ $ticket->user->last_first_name }}
    </div>

    <h4>Created On</h4>
    <div>
        {{ $ticket->created_at }}
    </div>

    <h4>Status</h4>
    <div>
        {{ $ticket->status }}
    </div>

    To view ticket go to TODO: get the link to the ticket


</div>
</html>