<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Message implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $sender_id;
    public $sender_name;
    public $content;
    public $created_at;
    public $receiver_id;
    public function __construct($sender_id,$content,$sender_name,$created_at,$receiver_id)
    {
        $this->sender_id = $sender_id;
        $this->content = $content;
        $this->sender_name = $sender_name;
        $this->created_at = $created_at;
        $this->receiver_id = $receiver_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['chat'];
    }
    public function broadcastAs()
    {
        return 'message';
    }
}
