<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Message;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Chat extends Component
{
    public $user;
    public $message;
    public $senderId;
    public $receiverId;

    public function mount($userId)
    {
       $this->user = $this->getUser($userId);

       $this->senderId = Auth::user()->id;
       $this->receiverId = $userId;
    }
    public function render()
    {
        return view('livewire.chat');
    }

    public function getUser($userId)
    {
        return User::find($userId);
    }

    public function sendMessage()
    {
        $this->seveMessage();
        $this->message = '';
    }

    public function seveMessage()
    {
        return Message::create([
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'message' => $this->message,
            // 'file_name' => '',
            // 'original_name' => '',
            // 'file_path'=> '',
            'is_read' => false
        ]);
    }
}
