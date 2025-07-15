#!/usr/bin/env node

const http = require('http');
const https = require('https');

console.log('🔍 Checking Reverb WebSocket Server...\n');

// Check if Reverb server is running on port 8080
const checkReverbServer = (port = 8080) => {
    return new Promise((resolve) => {
        const req = http.get(`http://localhost:${port}`, (res) => {
            console.log(`✅ Reverb server is running on port ${port}`);
            resolve(true);
        });

        req.on('error', (err) => {
            console.log(`❌ Reverb server is not running on port ${port}`);
            resolve(false);
        });

        req.setTimeout(3000, () => {
            console.log(`⏰ Timeout checking Reverb server on port ${port}`);
            resolve(false);
        });
    });
};

const main = async () => {
    const isRunning = await checkReverbServer(8080);
    
    if (!isRunning) {
        console.log('\n📋 Troubleshooting Steps:');
        console.log('1. Start the Reverb server:');
        console.log('   php artisan reverb:start');
        console.log('');
        console.log('2. Or run it in the background:');
        console.log('   php artisan reverb:start --daemon');
        console.log('');
        console.log('3. Check if the server is running:');
        console.log('   php artisan reverb:status');
        console.log('');
        console.log('4. Make sure your .env file has the correct Reverb settings:');
        console.log('   REVERB_APP_KEY=your_app_key');
        console.log('   REVERB_APP_SECRET=your_app_secret');
        console.log('   REVERB_APP_ID=your_app_id');
        console.log('   REVERB_HOST=localhost');
        console.log('   REVERB_PORT=8080');
        console.log('   REVERB_SCHEME=http');
        console.log('');
        console.log('5. For frontend, make sure these VITE variables are set:');
        console.log('   VITE_REVERB_APP_KEY=your_app_key');
        console.log('   VITE_REVERB_HOST=localhost');
        console.log('   VITE_REVERB_PORT=8080');
        console.log('   VITE_REVERB_SCHEME=http');
    } else {
        console.log('\n✅ Reverb server is running!');
        console.log('If you\'re still having WebSocket issues, check:');
        console.log('1. Browser console for connection errors');
        console.log('2. Network tab for failed WebSocket requests');
        console.log('3. Laravel logs for any server-side errors');
    }
};

main().catch(console.error); 