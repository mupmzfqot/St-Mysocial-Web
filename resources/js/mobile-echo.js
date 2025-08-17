// Example Echo configuration for mobile apps (React Native, Flutter, etc.)
// This file shows how to configure Echo for mobile apps using bearer token

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Initialize Echo for mobile app with bearer token authentication
 * @param {string} userToken - Bearer token from Laravel Sanctum
 * @param {object} config - Configuration object
 * @returns {Echo} Configured Echo instance
 */
export function initializeMobileEcho(userToken, config = {}) {
    const defaultConfig = {
        key: config.key || 'your-reverb-key',
        wsHost: config.wsHost || 'your-host',
        wsPort: config.wsPort || 443,
        forceTLS: true,
        encrypted: true,
        disableStats: true,
        cluster: config.cluster || 'your-cluster',
    };

    return new Echo({
        broadcaster: 'reverb',
        ...defaultConfig,
        auth: {
            headers: {
                'Authorization': `Bearer ${userToken}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        }
    });
}

/**
 * Subscribe to conversation channel
 * @param {Echo} echo - Echo instance
 * @param {number} conversationId - Conversation ID
 * @param {function} callback - Callback function for new messages
 */
export function subscribeToConversation(echo, conversationId, callback) {
    return echo.private(`conversation.${conversationId}`)
        .listen('NewMessage', callback);
}

/**
 * Subscribe to user notifications
 * @param {Echo} echo - Echo instance
 * @param {number} userId - User ID
 * @param {function} callback - Callback function for notifications
 */
export function subscribeToUserNotifications(echo, userId, callback) {
    return echo.private(`App.Models.User.${userId}`)
        .listen('*', callback);
}

/**
 * Subscribe to message notifications
 * @param {Echo} echo - Echo instance
 * @param {function} callback - Callback function for message notifications
 */
export function subscribeToMessageNotifications(echo, callback) {
    return echo.private('message.notification')
        .listen('NewMessage', callback);
}

/**
 * Disconnect Echo connection
 * @param {Echo} echo - Echo instance
 */
export function disconnectEcho(echo) {
    if (echo) {
        echo.disconnect();
    }
}

// Example usage:
/*
import { initializeMobileEcho, subscribeToConversation } from './mobile-echo';

// Initialize with user token
const echo = initializeMobileEcho('your-bearer-token-here', {
    key: 'your-reverb-key',
    wsHost: 'your-host',
    cluster: 'your-cluster'
});

// Subscribe to conversation
const subscription = subscribeToConversation(echo, 123, (e) => {
    // Handle new message
    handleNewMessage(e);
});

// Clean up when done
subscription.stop();
disconnectEcho(echo);
*/
