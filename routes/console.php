<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Events\NewMessage;
use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('test:broadcast', function () {
    $this->info('=== Testing Event Broadcasting ===');
    
    // Test 1: Check broadcasting configuration
    $this->info('1. Checking broadcasting configuration...');
    $this->info('   Default broadcaster: ' . config('broadcasting.default'));
    $this->info('   REVERB_APP_KEY: ' . (env('REVERB_APP_KEY') ? 'Set' : 'Not set'));
    $this->info('   REVERB_APP_SECRET: ' . (env('REVERB_APP_SECRET') ? 'Set' : 'Not set'));
    $this->info('   REVERB_APP_ID: ' . (env('REVERB_APP_ID') ? 'Set' : 'Not set'));
    $this->info('   REVERB_HOST: ' . (env('REVERB_HOST') ?: 'Not set'));
    
    // Test 2: Test NewMessage event
    $this->info('2. Testing NewMessage event...');
    try {
        $conversation = Conversation::first();
        if (!$conversation) {
            $this->error('   No conversation found in database');
            return;
        }
        
        $this->info('   Found conversation ID: ' . $conversation->id);
        
        // Test NewMessage event
        event(new NewMessage($conversation->id));
        $this->info('   ✅ NewMessage event dispatched successfully');
        
    } catch (\Exception $e) {
        $this->error('   ❌ NewMessage event failed: ' . $e->getMessage());
    }
    
    // Test 3: Test MessageSent event
    $this->info('3. Testing MessageSent event...');
    try {
        $message = Message::first();
        if (!$message) {
            $this->error('   No message found in database');
            return;
        }
        
        $this->info('   Found message ID: ' . $message->id);
        
        // Test MessageSent event
        event(new MessageSent($message));
        $this->info('   ✅ MessageSent event dispatched successfully');
        
    } catch (\Exception $e) {
        $this->error('   ❌ MessageSent event failed: ' . $e->getMessage());
    }
    
    // Test 4: Check channel authorization
    $this->info('4. Testing channel authorization...');
    try {
        $user = User::first();
        if (!$user) {
            $this->error('   No user found in database');
            return;
        }
        
        $this->info('   Testing with user ID: ' . $user->id);
        
        // Test message.notification channel
        $auth = \Illuminate\Support\Facades\Broadcast::channel('message.notification', $user);
        $this->info('   message.notification channel auth: ' . ($auth ? '✅ Authorized' : '❌ Not authorized'));
        
        // Test conversation channel
        if ($conversation) {
            $auth = \Illuminate\Support\Facades\Broadcast::channel('conversation.' . $conversation->id, $user, $conversation->id);
            $this->info('   conversation.' . $conversation->id . ' channel auth: ' . ($auth ? '✅ Authorized' : '❌ Not authorized'));
        }
        
    } catch (\Exception $e) {
        $this->error('   ❌ Channel authorization failed: ' . $e->getMessage());
    }
    
    $this->info('=== End Testing ===');
})->purpose('Test event broadcasting functionality');

Artisan::command('debug:broadcast', function () {
    $this->info('=== Debug Event Broadcasting ===');
    
    // Test 1: Check environment variables
    $this->info('1. Environment Variables:');
    $this->info('   APP_ENV: ' . env('APP_ENV'));
    $this->info('   APP_DEBUG: ' . (env('APP_DEBUG') ? 'true' : 'false'));
    $this->info('   BROADCAST_CONNECTION: ' . env('BROADCAST_CONNECTION'));
    $this->info('   QUEUE_CONNECTION: ' . env('QUEUE_CONNECTION'));
    $this->info('   REVERB_APP_KEY: ' . (env('REVERB_APP_KEY') ? 'Set' : 'Not set'));
    $this->info('   REVERB_APP_SECRET: ' . (env('REVERB_APP_SECRET') ? 'Set' : 'Not set'));
    $this->info('   REVERB_APP_ID: ' . (env('REVERB_APP_ID') ? 'Set' : 'Not set'));
    $this->info('   REVERB_HOST: ' . (env('REVERB_HOST') ?: 'Not set'));
    $this->info('   REVERB_PORT: ' . (env('REVERB_PORT') ?: '443'));
    $this->info('   REVERB_SCHEME: ' . (env('REVERB_SCHEME') ?: 'https'));
    
    // Test 2: Check broadcasting config
    $this->info('2. Broadcasting Configuration:');
    $this->info('   Default: ' . config('broadcasting.default'));
    $this->info('   Reverb driver: ' . config('broadcasting.connections.reverb.driver'));
    $this->info('   Reverb host: ' . config('broadcasting.connections.reverb.options.host'));
    $this->info('   Reverb port: ' . config('broadcasting.connections.reverb.options.port'));
    $this->info('   Reverb scheme: ' . config('broadcasting.connections.reverb.options.scheme'));
    
    // Test 3: Test event with detailed logging
    $this->info('3. Testing NewMessage event with logging...');
    try {
        $conversation = Conversation::first();
        if (!$conversation) {
            $this->error('   No conversation found');
            return;
        }
        
        $this->info('   Using conversation ID: ' . $conversation->id);
        
        // Enable detailed logging
        \Log::info('Testing NewMessage event', [
            'conversation_id' => $conversation->id,
            'timestamp' => now(),
            'user_id' => auth()->id() ?? 'not authenticated'
        ]);
        
        // Dispatch event
        event(new NewMessage($conversation->id));
        
        $this->info('   ✅ Event dispatched successfully');
        $this->info('   Check Laravel logs for detailed information');
        
    } catch (\Exception $e) {
        $this->error('   ❌ Event failed: ' . $e->getMessage());
        $this->error('   Stack trace: ' . $e->getTraceAsString());
    }
    
    // Test 4: Check if Reverb is running
    $this->info('4. Checking Reverb service...');
    try {
        $host = env('REVERB_HOST', 'localhost');
        $port = env('REVERB_PORT', 443);
        
        $this->info("   Testing connection to $host:$port");
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($connection) {
            $this->info('   ✅ Reverb service is reachable');
            fclose($connection);
        } else {
            $this->error("   ❌ Cannot connect to Reverb service: $errstr ($errno)");
        }
        
    } catch (\Exception $e) {
        $this->error('   ❌ Connection test failed: ' . $e->getMessage());
    }
    
    $this->info('=== End Debug ===');
})->purpose('Debug event broadcasting issues');

Artisan::command('monitor:reverb', function () {
    $this->info('=== Monitor Reverb Service ===');
    
    // Check if Reverb is running
    $this->info('1. Checking Reverb service status...');
    
    $host = env('REVERB_HOST', 'localhost');
    $port = env('REVERB_PORT', 443);
    
    $this->info("   Host: $host");
    $this->info("   Port: $port");
    
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($connection) {
        $this->info('   ✅ Reverb service is running');
        fclose($connection);
    } else {
        $this->error("   ❌ Reverb service is not running: $errstr ($errno)");
        $this->info('   💡 Try running: php artisan reverb:start');
        return;
    }
    
    // Test broadcasting with detailed output
    $this->info('2. Testing event broadcasting...');
    try {
        $conversation = Conversation::first();
        if (!$conversation) {
            $this->error('   No conversation found');
            return;
        }
        
        $this->info("   Testing with conversation ID: {$conversation->id}");
        
        // Create a test event with detailed logging
        $event = new NewMessage($conversation->id);
        
        $this->info('   Event details:');
        $this->info('   - Channel: ' . $event->broadcastOn()[0]->name);
        $this->info('   - Event class: ' . get_class($event));
        $this->info('   - Should broadcast now: ' . (method_exists($event, 'shouldBroadcastNow') ? 'Yes' : 'No'));
        
        // Dispatch event
        event($event);
        
        $this->info('   ✅ Event dispatched to Reverb');
        $this->info('   💡 Check Reverb logs for confirmation');
        
    } catch (\Exception $e) {
        $this->error('   ❌ Event broadcasting failed: ' . $e->getMessage());
    }
    
    // Check Laravel logs for broadcasting
    $this->info('3. Checking recent Laravel logs...');
    $logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        $recentLogs = array_slice($lines, -10);
        
        $this->info('   Recent log entries:');
        foreach ($recentLogs as $log) {
            if (strpos($log, 'broadcast') !== false || strpos($log, 'NewMessage') !== false) {
                $this->line('   ' . $log);
            }
        }
    } else {
        $this->info('   No log file found for today');
    }
    
    $this->info('=== End Monitor ===');
})->purpose('Monitor Reverb service and event broadcasting');

Artisan::command('test:sendmessage', function () {
    $this->info('=== Test SendMessage Action ===');
    
    try {
        $conversation = Conversation::first();
        if (!$conversation) {
            $this->error('No conversation found');
            return;
        }
        
        // Get a user that is part of this conversation
        $user = $conversation->users->first();
        if (!$user) {
            $this->error('No user found in conversation');
            return;
        }
        
        $this->info("Testing with conversation ID: {$conversation->id}");
        $this->info("Testing with user ID: {$user->id}");
        $this->info("User name: {$user->name}");
        
        // Check conversation users
        $this->info("Conversation users:");
        foreach ($conversation->users as $convUser) {
            $this->info("  - User ID: {$convUser->id}, Name: {$convUser->name}");
        }
        
        // Check if user is in conversation
        $isInConversation = $conversation->users->contains($user->id);
        $this->info("User in conversation: " . ($isInConversation ? 'Yes' : 'No'));
        
        // Create a mock request
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'message' => 'Test message from command line',
            'files' => []
        ]);
        
        // Mock authentication properly
        auth()->login($user);
        
        // Verify authentication
        $this->info("Authenticated user: " . (auth()->check() ? auth()->user()->name : 'Not authenticated'));
        
        // Test policy directly
        $policy = new \App\Policies\ConversationPolicy();
        $canSend = $policy->send($user, $conversation);
        $this->info("Policy check - can send: " . ($canSend ? 'Yes' : 'No'));
        
        // Create SendMessage action instance
        $sendMessage = new \App\Actions\Messages\SendMessage();
        
        // Execute the action
        $response = $sendMessage->handle($request, $conversation->id);
        
        $this->info('Response:');
        $this->info(json_encode($response->getData(), JSON_PRETTY_PRINT));
        
        if ($response->getData()->error === 0) {
            $this->info('✅ SendMessage action executed successfully');
        } else {
            $this->error('❌ SendMessage action failed');
        }
        
    } catch (\Exception $e) {
        $this->error('❌ Test failed: ' . $e->getMessage());
        $this->error('Stack trace: ' . $e->getTraceAsString());
    }
    
    $this->info('=== End Test ===');
})->purpose('Test SendMessage action directly');
