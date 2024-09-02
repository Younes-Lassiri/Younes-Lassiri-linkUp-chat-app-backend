<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\Message;
class ChatController extends Controller
{
    public function message(Request $request)
    {
        event(new Message($request->input('sender_id'),$request->input('content'), $request->input('sender_name'),$request->input('created_at'),$request->input('receiver_id')));
        return [];
    }
}
