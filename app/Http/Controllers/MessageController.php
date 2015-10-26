<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
use App\Models\Message;
use DB;

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
        return view('messages.index');
    }


    /** GET: /messages/all
     *
     * Returns all messages to the user.
     *
     * @return \Illuminate\Http\Response An array of all messages as JSON.
     */
    public function getAll()
    {
        return response()->json(Message::all());
    }

    /** GET: /messages/all-recipients
     *
     * Returns all users available for receiving messages.
     *
     * @return \Illuminate\Http\Response An array of all users as JSON.
     */
    public function getAllRecipients()
    {
        // Good luck doing this efficiently with Eloquent.
        // Selects all users who teach at least one course.
        // least_num_orders is the lowest number of orders that any of their courses has.
        // 0 indicates one of their courses has no orders, anything higher indicates that
        // all of their courses have at least one order.
        // TODO: restrict this query to courses from the current term only.
        // TODO: have least_num_orders skip courses that are marked as no book.
        $usersWithCourses = DB::select( DB::raw(
            "SELECT DISTINCT(users.user_id), first_name, last_name,
             MIN((SELECT count(*) FROM `orders` WHERE courses.course_id=orders.course_id)) as least_num_orders
             FROM `courses` JOIN `users` ON courses.user_id = users.user_id GROUP BY users.user_id"));

        return response()->json($usersWithCourses);
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
        $cloneFrom = $request->get('message_id');

        if (is_null($cloneFrom))
        {
            $message = new Message();
            $message->subject = "EWU Textbook Requests";
            $message->body = "<h3>New Message</h3>";
            $message->owner_user_id = session('user_id');
            $message->save();
        }
        else
        {
            $message = Message::findOrFail((int)$cloneFrom)->replicate();
            $message->owner_user_id = session('user_id');
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
        if ($message->owner_user_id != session('user_id'))
        {
            return response()->json(['success' => false, 'error' => 'You do not own that message'], Response::HTTP_FORBIDDEN);
        };

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
        if ($message->owner_user_id != session('user_id'))
        {
            return response()->json(['success' => false, 'error' => 'You do not own that message'], Response::HTTP_FORBIDDEN);
        };

        $message->update($request->only(['subject', 'body']));

        return response()->json(['success' => true]);
    }
}
