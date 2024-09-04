<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;
use App\Models\User;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::post('/api/signup', [UserController::class, 'signup']);

Route::post('/api/login', [UserController::class, 'login']);

Route::post('/api/users', [UserController::class, 'users']);

Route::post('/api/add-friend', [UserController::class, 'invitation']);

Route::get('/api/friend-request', [UserController::class, 'friendRequest']);

Route::post('/api/accept-request', [UserController::class, 'acceptRequest']);

Route::get('/api/friends', [UserController::class, 'friends']);


Route::post('/api/send-message', [UserController::class, 'sendMessage']);
Route::post('/api/message', [ChatController::class, 'message']);
Route::get('/api/messages', [UserController::class, 'getMessagesWithFriend']);



Route::post('/api/upload-picture', [UserController::class, 'uploadPicture']);

Route::post('/api/set-satus', [UserController::class, 'changeStatus']);

Route::get('/api/check-auth', [UserController::class, 'checkAuth']);

Route::post('/api/logout', [UserController::class, 'logout']);
Route::post('/api/user/activity', [UserController::class, 'updateActivity']);
Route::get('/api/online-users', [UserController::class, 'getOnlineFriends']);

Route::get('/api/users-fetch', function(){
    $users = User::get();
    return response()->json($users);
});
