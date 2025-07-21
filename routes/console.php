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
