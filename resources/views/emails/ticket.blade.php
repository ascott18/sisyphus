

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