<?php

namespace App\Actions\Migrations;

use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Messages
{
    public function handle()
    {
        $chats = DB::connection('mysql_2')->table('chats')
            ->where('removeAt', 0)
            ->get();

        foreach ($chats as $chat) {
            $senderExist = User::find($chat->fromUserId);
            $receiverExist = User::find($chat->toUserId);

            if($senderExist && $receiverExist) {
                $conversation = Conversation::query()->firstOrCreate([
                    'id'    => $chat->id,
                    'type'  => 'private',
                ]);

                ConversationUser::query()->firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id'         => $chat->fromUserId,
                    'role'            => 'member'
                ]);

                ConversationUser::query()->firstOrCreate([
                    'conversation_id' => $conversation->id,
                    'user_id'         => $chat->toUserId,
                    'role'            => 'member'
                ]);

                $messages = DB::connection('mysql_2')->table('messages')
                    ->where('removeAt', 0)
                    ->where('chatId', $chat->id)
                    ->get();

                foreach ($messages as $message) {
                    Message::query()->firstOrCreate([
                        'conversation_id' => $message->chatId,
                        'sender_id'         => $message->fromUserId,
                        'content'         => $message->message,
                        'is_read'         => 1,
                    ]);
                }
            }
        }


    }
}
