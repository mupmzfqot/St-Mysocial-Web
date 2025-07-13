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

            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'content' => $request->message,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $message->addMedia($file)
                        ->toMediaCollection('message_media');
                }
            }

            $message->load('sender', 'media');

            Event::dispatch(new MessageSent($message));
            Event::dispatch(new NewMessage($conversation_id));

            $recipient = $conversation->otherUser($request->user()->id)->first();

            if($recipient) {
                $recipient->notify(new NewMessageNotification($message, $request->user()));
            }

            return response()->json([
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->name,
                'media' => array_values($message->getMedia('message_media')->toArray())
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ]);
        }
    }
}