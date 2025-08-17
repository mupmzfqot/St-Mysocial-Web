<?php

namespace App\Services;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BroadcastService
{
    /**
     * Broadcast an event to both web and API channels
     *
     * @param ShouldBroadcast $event
     * @return void
     */
    public static function broadcastToBoth(ShouldBroadcast $event): void
    {
        // Broadcast to web channels (existing functionality)
        broadcast($event);
        
        // Broadcast to API channels (new functionality)
        if (method_exists($event, 'broadcastOn')) {
            $channels = $event->broadcastOn();
            
            // Convert web channels to API channels
            $apiChannels = collect($channels)->map(function ($channel) {
                if ($channel instanceof PrivateChannel) {
                    $channelName = $channel->name;
                    // Convert web channel names to API channel names
                    if (str_starts_with($channelName, 'App.Models.User.')) {
                        return new PrivateChannel('api.' . $channelName);
                    }
                    if (str_starts_with($channelName, 'conversation.')) {
                        return new PrivateChannel('api.' . $channelName);
                    }
                    if ($channelName === 'message.notification') {
                        return new PrivateChannel('api.' . $channelName);
                    }
                }
                return $channel;
            })->filter();
            
            // Broadcast to API channels if any exist
            if ($apiChannels->isNotEmpty()) {
                foreach ($apiChannels as $channel) {
                    broadcast($event)->toOthers()->on($channel);
                }
            }
        }
    }
    
    /**
     * Get API channel name from web channel name
     *
     * @param string $webChannelName
     * @return string
     */
    public static function getApiChannelName(string $webChannelName): string
    {
        return 'api.' . $webChannelName;
    }
    
    /**
     * Check if a channel name is an API channel
     *
     * @param string $channelName
     * @return bool
     */
    public static function isApiChannel(string $channelName): bool
    {
        return str_starts_with($channelName, 'api.');
    }
}
