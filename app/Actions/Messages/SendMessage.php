<?php 

namespace App\Actions\Messages;

use App\Events\MessageSent;
use App\Events\NewMessage;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Log;

class SendMessage
{
    public function handle(Request $request, $conversation_id)
    {
        try {
            $request->validate([
                'files' => 'nullable|array',
                'files.*' => [
                    'file',
                    'mimetypes:image/jpeg,image/png,image/jpg,application/pdf',
                    'max:10240' // 10MB
                ],
                'message' => 'required_without:files'
            ]);

            if(!$request->message && !$request->hasFile('files')) {
                return response()->json([
                    'message' => 'Please enter a message or attach a file.'
                ]);
            }

            $conversation = Conversation::find($conversation_id);
                
            Gate::authorize('send', $conversation);

            $currentUser = $request->user();
            if (!$currentUser) {
                Log::error('No authenticated user found', [
                    'conversation_id' => $conversation_id,
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'error' => 1,
                    'message' => 'User not authenticated'
                ]);
            }

            $message = $conversation->messages()->create([
                'sender_id' => $currentUser->id,
                'content' => $request->message,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $message->addMedia($file)
                        ->toMediaCollection('message_media');
                }
            }

            $message->load('sender', 'media');

            // Try to broadcast events, but don't fail if WebSocket is not available
            try {
                Log::info('Broadcasting events for message', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversation_id,
                    'sender_id' => $request->user()->id
                ]);
                
                Event::dispatch(new MessageSent($message));
                Log::info('MessageSent event dispatched successfully');
                
                Event::dispatch(new NewMessage($conversation_id));
                Log::info('NewMessage event dispatched successfully');
                
            } catch (\Exception $e) {
                // Log the WebSocket error but don't fail the request
                Log::warning('WebSocket broadcast failed for message ' . $message->id . ': ' . $e->getMessage(), [
                    'exception' => $e->getTraceAsString()
                ]);
            }

            $currentUser = $request->user();
            if ($currentUser) {
                $recipient = $conversation->otherUser($currentUser->id)->first();

                if($recipient) {
                    try {
                        $recipient->notify(new NewMessageNotification($message, $currentUser));
                    } catch (\Exception $e) {
                        // Log notification error but don't fail the request
                        Log::warning('Notification failed for message ' . $message->id . ': ' . $e->getMessage());
                    }
                }
            } else {
                Log::warning('No authenticated user found for message notification', [
                    'message_id' => $message->id,
                    'conversation_id' => $conversation_id
                ]);
            }

            return response()->json([
                'error' => 0,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->name,
                    'media' => array_values($message->getMedia('message_media')->toArray())
                ]
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'error' => 1,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}