<?php
namespace App\Livewire;

use App\Models\User;
use App\Models\Message;
use Livewire\Component;
use App\Events\userTyping;
use Livewire\Attributes\On;
use App\Events\MessageSentEvent;
use Illuminate\Support\Facades\Auth;

class Chat extends Component
{
    public $user;
    public $message;
    public $senderId;
    public $receiverId;
    public $messages;

    public function mount($userId)
    {
        # Scroll to the bottom of the chat
        $this->dispatch('messages-updated');

        $this->user = $this->getUser($userId);

        $this->senderId   = Auth::user()->id;
        $this->receiverId = $userId;
        $this->messages   = $this->getMessages();
    }
    public function render()
    {
        return view('livewire.chat');
    }

    public function getMessages()
    {
        return Message::with(['sender:id,name', 'receiver:id,name'])->where(function ($query) {
            $query->where('sender_id', $this->senderId)
                ->where('receiver_id', $this->receiverId);
        })->orWhere(function ($query) {
            $query->where('sender_id', $this->receiverId)
                ->where('receiver_id', $this->senderId);
        })->get();
    }

    /**
     * Function to check if user is typing
     */
    public function userTyping()
    {
        broadcast(new userTyping($this->senderId, $this->receiverId))->toOthers();
    }

    public function getUser($userId)
    {
        return User::find($userId);
    }

    public function sendMessage()
    {
        # Save the message
        $sentMessage = $this->saveMessage();

        # Assign the latest message
        $this->messages[] = $sentMessage;

        # Broadcast the message
        broadcast(new MessageSentEvent($sentMessage));

        $this->message = '';

        # Scroll to the bottom of the chat
        // $this->dispatch('messages-updated');
    }

    #[On('echo-private:chat-channel.{senderId},MessageSentEvent')]
    public function listenMessage($event)
    {
        $newMessage       = Message::find($event['message']['id'])->load('sender:id,name', 'receiver:id,name');
        $this->messages[] = $newMessage;
    }

    public function saveMessage()
    {
        return Message::create([
            'sender_id'   => $this->senderId,
            'receiver_id' => $this->receiverId,
            'message'     => $this->message,
            // 'file_name' => '',
            // 'original_name' => '',
            // 'file_path'=> '',
            'is_read'     => false,
        ]);
    }
}
