<?php
namespace App\Livewire;

use App\Events\MessageSentEvent;
use App\Events\UnreadMessage;
use App\Events\userTyping;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Chat extends Component
{
    use WithFileUploads;

    public $user;
    public $message;
    public $senderId;
    public $receiverId;
    public $messages;
    public $file;

    /**
     * Mount the component.
     */
    public function mount($userId)
    {
        # Scroll to the bottom of the chat
        $this->dispatch('messages-updated');

        $this->user = $this->getUser($userId);

        $this->senderId   = Auth::user()->id;
        $this->receiverId = $userId;

        # Get the messages
        $this->messages = $this->getMessages();

        # Read the messages
        $this->markMessageAsRead();
    }

    /**
     * Render the component.
     * @return \Illuminate\View\View
     */
    public function render()
    {
        # Read the messages
        $this->markMessageAsRead();

        return view('livewire.chat');
    }

    /**
     * Function to get messages
     */
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

    /**
     * Function to read message
     */
    public function markMessageAsRead()
    {
        Message::where('sender_id', $this->receiverId)
            ->where('receiver_id', $this->senderId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Function to get user
     */
    public function getUser($userId)
    {
        return User::find($userId);
    }

    /**
     * Function to send message
     * @return void
     */
    public function sendMessage()
    {
        # Save the message
        $sentMessage = $this->saveMessage();

        # Assign the latest message
        $this->messages[] = $sentMessage;

        # Broadcast the message
        broadcast(new MessageSentEvent($sentMessage));

        # Get the unread message count
        $unreadMessageCount = $this->getUnreadMessageCount();

        # Broadcast the unread message
        broadcast(new UnreadMessage($this->senderId, $this->receiverId, $unreadMessageCount))->toOthers();

        $this->message = null;

        $this->file = null;

        # Scroll to the bottom of the chat
        $this->dispatch('messages-updated');
    }

    /**
     * Function: getUnreadMessageCount
     * @return int count
     */
    public function getUnreadMessageCount()
    {
        return Message::where('receiver_id', $this->receiverId)->where('is_read', false)->count();
    }

    /**
     * Function: listenMessage
     * @return void
     */
    #[On('echo-private:chat-channel.{senderId},MessageSentEvent')]
    public function listenMessage($event)
    {
        $newMessage       = Message::find($event['message']['id'])->load('sender:id,name', 'receiver:id,name');
        $this->messages[] = $newMessage;
    }

    /**
     * Function: saveMessage
     * @return void
     */
    public function saveMessage()
    {
        #file handling
        $fileName         = null;
        $fileOriginalName = null;
        $filePath         = null;
        $fileType         = null;

        if ($this->file) {
            $fileName         = $this->file->hashName();
            $fileOriginalName = $this->file->getClientOriginalName();
            $filePath         = $this->file->store('chat-files', 'public');
            $fileType         = $this->file->getMimeType();
        }

        #save the message
        return Message::create([
            'sender_id'     => $this->senderId,
            'receiver_id'   => $this->receiverId,
            'message'       => $this->message,
            'file_name'     => $fileName,
            'original_name' => $fileOriginalName,
            'file_path'     => $filePath,
            'file_type'     => $fileType,
            'is_read'       => false,
        ]);
    }
}
