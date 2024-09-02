<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Friend;
use App\Models\Message;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'status' => 'Available',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        return response()->json([
            'message' => 'User signed u successfully!'
        ],200);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            return response()->json([
                'message' => "You're logged in",
                'user' => $user
            ], 200);
        } else {
            return response()->json([
                'message' => "Invalid credentials"
            ], 401);
        }
    }
    public function users(Request $request)
    {
        $authUserId = auth()->id();
        $users = User::where('id', '!=', $authUserId)
            ->whereDoesntHave('sentFriendRequests', function ($query) use ($authUserId) {
                $query->where('sender_id', $authUserId)
                      ->orWhere('user_id', $authUserId);
            })
            ->whereDoesntHave('receivedFriendRequests', function ($query) use ($authUserId) {
                $query->where('sender_id', $authUserId)
                      ->orWhere('user_id', $authUserId);
            })
            ->get();
    
        return response()->json($users);
    }
    

    public function invitation(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);
        $user = User::findOrFail($request->id);
        Friend::create([
            'name' => $user->name,
            'user_id' => $user->id,
            'sender_id' => auth()->user()->id,
            'status' => "pending"
        ]);
        return response()->json([
            'message' => 'Friend request sent successfully!'
        ],200);
    }
    public function friendRequest()
    {
        $friendsRequest = Friend::where('user_id', auth()->user()->id)
                                ->where('status', 'pending')
                                ->pluck('sender_id');
        $users = User::whereIn('id', $friendsRequest)->get();
        return response()->json($users);
    }
    public function acceptRequest(Request $request)
    {
        $sender_id = $request->id;
        $friend = Friend::where('sender_id', $sender_id)
                        ->where('user_id', auth()->user()->id)
                        ->where('status', 'pending')
                        ->first();
        if ($friend) {
            $friend->status = 'accepted';
            $friend->save();
            return response()->json(['message' => 'Friend request accepted successfully.']);
        } else {
            return response()->json(['message' => 'Friend request not found or already accepted.'], 404);
        }
    }
    public function friends()
    {
        $userId = auth()->user()->id;
        $threshold = now()->subMinutes(2);
        $friendsRequest = Friend::where(function ($query) use ($userId) {
                                    $query->where('user_id', $userId)
                                          ->orWhere('sender_id', $userId);
                                })
                                ->where('status', 'accepted')
                                ->get();
    
        $friendIds = $friendsRequest->map(function ($friend) use ($userId) {
            return $friend->user_id == $userId ? $friend->sender_id : $friend->user_id;
        })->toArray();
        $users = User::select('id', 'name', 'picture','status', 'last_activity')
                     ->whereIn('id', $friendIds)
                     ->get()
                     ->map(function ($user) use ($threshold) {
                         $user->state = ($user->last_activity && now()->diffInMinutes($user->last_activity) < 2)
                                        ? 'online'
                                        : 'offline';
                         return $user;
                     });
    
        return response()->json($users);
    }

    public function getMessagesWithFriend(Request $request)
    {
        $userId = auth()->user()->id;
        $friendId = $request->query('friendId');

        if (!$friendId) {
            return response()->json(['error' => 'Friend ID is required'], 400);
        }

        $messages = Message::where(function($query) use ($userId, $friendId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $friendId);
        })->orWhere(function($query) use ($userId, $friendId) {
            $query->where('sender_id', $friendId)
                ->where('receiver_id', $userId);
        })->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
            'receiver_name' => 'required'
        ]);

        $userId = auth()->user()->id;
        $userName = auth()->user()->name;
        $message = Message::create([
            'sender_id' => $userId,
            'receiver_id' => $request->receiver_id,
            'content' => $request->content,
            'sender_name' => $userName,
            'receiver_name' => $request->receiver_name
        ]);
        return response()->json([
            'message' => 'message sent successfully!'
        ],201);
    }
    public function uploadPicture(Request $request)
    {
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $file = $request->file('picture');
        $filename = time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/images', $filename);
        $user = Auth::user();
        $user->picture = 'storage/images/' . $filename;
        $user->save();

        return response()->json(['message' => 'Picture uploaded successfully']);
    }
    public function changeStatus(Request $request)
    {
        $userId = auth()->user()->id;
        $user = User::findOrFail($userId);

        if ($user->status === 'Available') {
            $user->status = 'Busy';
        } else {
            $user->status = 'Available';
        }

        $user->save();

        return response()->json(['message' => 'Status updated successfully', 'status' => $user->status]);
    }
    public function checkAuth()
    {
        if (auth()->check()) {
            return response()->json([
                'authenticated' => true,
                'user' => auth()->user()
            ]);
        } else {
            return response()->json(['authenticated' => false]);
        }
    }
    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Successfully logged out']);
    }
    public function updateActivity(Request $request)
    {
        $user = Auth::user();
        $user->last_activity = now();
        $user->save();
        return response()->json(['message' => 'Activity updated']);
    }
    public function getOnlineFriends()
    {
        $userId = auth()->user()->id;
        $threshold = now()->subMinutes(2);
        $friendsRequest = Friend::where(function ($query) use ($userId) {
                                    $query->where('user_id', $userId)
                                        ->orWhere('sender_id', $userId);
                                })
                                ->where('status', 'accepted')
                                ->get();

        $friendIds = $friendsRequest->map(function ($friend) use ($userId) {
            return $friend->user_id == $userId ? $friend->sender_id : $friend->user_id;
        })->toArray();
        $users = User::select('id', 'name','status', 'picture', 'last_activity')
                    ->whereIn('id', $friendIds)
                    ->where('last_activity', '>', $threshold)
                    ->get()
                    ->map(function ($user) {
                        $user->state = 'online';
                        return $user;
                    });

        return response()->json($users);
    }
}
