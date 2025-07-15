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
        console.log('   REVERB_APP_KEY=ioipjibtntmk8ogipqtg');
        console.log('   REVERB_APP_SECRET=smhumec4rlnliqydifhs');
        console.log('   REVERB_APP_ID=837724');
        console.log('   REVERB_HOST=social_web.test');
        console.log('   REVERB_PORT=8080');
        console.log('   REVERB_SCHEME=https');
        console.log('');
        console.log('5. For frontend, make sure these VITE variables are set:');
        console.log('   VITE_REVERB_APP_KEY=ioipjibtntmk8ogipqtg');
        console.log('   VITE_REVERB_HOST=social_web.test');
        console.log('   VITE_REVERB_PORT=8080');
        console.log('   VITE_REVERB_SCHEME=https');
        console.log('');
        console.log('6. Check if your domain is accessible:');
        console.log('   curl -I https://social_web.test:8080');
    } else {
        console.log('\n✅ Reverb server is running!');
        console.log('If you\'re still having WebSocket issues, check:');
        console.log('1. Browser console for connection errors');
        console.log('2. Network tab for failed WebSocket requests');
        console.log('3. Laravel logs for any server-side errors');
        console.log('4. Make sure your domain (social_web.test) is accessible');
        console.log('5. Check if HTTPS is properly configured for local development');
    }
};

main().catch(console.error); 