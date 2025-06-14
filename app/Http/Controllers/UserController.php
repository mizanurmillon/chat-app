<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index() : View
    {
        $users = User::where('id', '!=', Auth::user()->id)->withCount(['unreadMessages'])->get();
        return view('dashboard', compact('users'));
    }

    public function userChat(int $userId) : View
    {
        return view('chat.user-chat', compact('userId'));
    }
}
