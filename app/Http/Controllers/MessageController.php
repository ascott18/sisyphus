<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Term;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use App\Models\Message;
use DB;
use Mail;

class MessageController extends Controller
{
    /** GET: /messages/
     *
     * Displays the message management view.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $this->authorize('send-messages');

        return view('messages.index');
    }


    /** GET: /messages/all-messages
     *
     * Returns all messages to the user.
     *
     * @return \Illuminate\Http\Response An array of all messages as JSON.
     */
    public function getAllMessages()
    {
        $this->authorize('send-messages');

        return Auth::user()->messages;
    }

    /** GET: /messages/all-recipients
     *
     * Returns all users available for receiving messages.
     *
     * @return \Illuminate\Http\Response An array of all users as JSON.
     */
    public function getAllRecipients(Request $request)
    {
        $this->authorize('send-messages');

        // Good luck doing this efficiently with Eloquent.
        // Selects all users who teach at least one course.
        // least_num_orders is the lowest number of orders that any of their courses has.
        // 0 indicates one of their courses has no orders, anything higher indicates that
        // all of their courses have at least one order.
        // TODO: restrict this query to courses from the current term only.
        // TODO: restrict this to only users that the current user should be able to send messages to.
        // TODO: have least_num_orders skip courses that are marked as no book.

        $user = $request->user();
        $terms = Term::currentTerms()->get();

        $currentTermIds = [];
        foreach ($terms as $term) {
            $currentTermIds[] = $term->term_id;
            $term['display_name'] = $term->displayName();
        }
        $departments = $user->departments()->lists('department');


        $maySendAll = $user->may('send-all-messages');

        $query = User
            ::where('users.net_id', '!=', 'tba')
            ->select(DB::raw(
                "users.first_name, users.last_name, users.user_id,
                COUNT(courses.no_book_marked IS NOT NULL OR (SELECT 1 FROM `orders` WHERE courses.course_id=orders.course_id LIMIT 1) > 0) as coursesResponded,
                COUNT(course_id) as courseCount"))
            ->join('courses', 'courses.user_id', '=', 'users.user_id')
            ->whereIn('courses.term_id', $currentTermIds);

        if (!$maySendAll){
            $query = $query->whereIn('department', $departments);
        }

        $usersWithCourses = $query
            ->groupBy('users.user_id')
            ->get();


        return [
            'users' => $usersWithCourses,
            'terms' => $terms
        ];
    }



    /** POST: /messages/create-message?message_id={message_id}
     *
     * Create a new message, and send it back as JSON.
     * If message_id is specified, the new message will be cloned from that message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response The new message as JSON.
     */
    public function postCreateMessage(Request $request)
    {
        $this->authorize('send-messages');

        $cloneFrom = $request->get('message_id');

        if (is_null($cloneFrom))
        {
            $message = new Message();
            $message->subject = "EWU Textbook Requests";
            $message->body = "<h3>New Message</h3>";
            $message->owner_user_id = Auth::user()->user_id;
            $message->save();
        }
        else
        {
            $message = Message::findOrFail((int)$cloneFrom);
            $this->authorize('touch-message', $message);

            $message = $message->replicate();
            $message->owner_user_id = Auth::user()->user_id;
            $message->subject = $message->subject . " Copy";
            $message->save();
        }

        return response()->json($message);
    }


    /** POST: /messages/delete-message?message_id={message_id}
     *
     * Delete the specified message. Will fail if the current user does not own the message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response The new message as JSON.
     */
    public function postDeleteMessage(Request $request)
    {
        $message_id = (int)$request->get('message_id');
        $message = Message::findOrFail($message_id);

        $this->authorize('touch-message', $message);

        $message->delete();

        return response()->json(['success' => true]);
    }



    /** POST: /messages/save-message?message_id={message_id}&subject={subject}&body={body}
     *
     * Saves a message. Will fail if the current user is not the owner, or if the message does not exist.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response The new message as JSON.
     */
    public function postSaveMessage(Request $request)
    {
        $message_id = (int)$request->get('message_id');
        $message = Message::findOrFail($message_id);

        $this->authorize('touch-message', $message);

        $message->update($request->only(['subject', 'body']));

        return response()->json(['success' => true]);
    }


    public function postSendMessages(Request $request)
    {
        $message = $request->get('message');

        $dbMessage = Message::findOrFail($message['message_id']);

        $this->authorize('touch-message', $dbMessage);
        $this->authorize('send-messages');

        $dbMessage->update(['last_sent' => Carbon::now()]);

        $recipientIds = $request->get('recipients');

        foreach ($recipientIds as $user_id)
        {
            $recipient = User::find($user_id);

            if ($recipient && $recipient->email && Auth::user()->can('send-message-to-user', $recipient))
            {
                Mail::queue([], [], function ($m) use ($recipient, $message) {
                    $email = $recipient->email;
                    // TODO: change this from address.
                    $m->from("postmaster@example.com", "Book Orders");
                    $m->to($email, $recipient->first_name . ' ' . $recipient->last_name);
                    $m->subject($message['subject']);
                    $m->setBody($message['body'], 'text/html');
                });
            }
        }
    }
}
