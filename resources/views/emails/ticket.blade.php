{{--This is the view is the view that should be emailed to whom it may concern when a new ticket is created, it needs some love--}}

<!DOCTYPE html>

<html lang="en">

<div>
    <h3>
        {{$ticket->title}}
    </h3>
    <p style="text-indent: 10px">
        {!!html_entity_decode($ticket->body)!!}
    </p>

    <a href={{ $link }}>
        View Ticket
    </a>


</div>
</html>