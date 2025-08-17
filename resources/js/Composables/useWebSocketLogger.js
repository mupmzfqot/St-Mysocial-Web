/**
 * WebSocket Logger Utility
 * Provides conditional logging for WebSocket operations
 * Can be enabled/disabled based on environment or user preference
 */

export function useWebSocketLogger() {
    // Check if logging should be enabled
    const isLoggingEnabled = () => {
        // Enable in development or when explicitly set
        return import.meta.env.DEV || 
               import.meta.env.VITE_WS_LOGGING === 'true' ||
               localStorage.getItem('ws_logging') === 'true';
    };

    // Log levels
    const log = (message, data = null) => {
        if (isLoggingEnabled()) {
            if (data) {
                console.log(`[WebSocket] ${message}`, data);
            } else {
                console.log(`[WebSocket] ${message}`);
            }
        }
    };

    const warn = (message, data = null) => {
        if (isLoggingEnabled()) {
            if (data) {
                console.warn(`[WebSocket] ${message}`, data);
            } else {
                console.warn(`[WebSocket] ${message}`);
            }
        }
    };

    const error = (message, data = null) => {
        if (isLoggingEnabled()) {
            if (data) {
                console.error(`[WebSocket] ${message}`, data);
            } else {
                console.error(`[WebSocket] ${message}`);
            }
        }
    };

    // Connection status logging
    const logConnection = (status, details = null) => {
        if (isLoggingEnabled()) {
            const emoji = {
                'connected': '✅',
                'disconnected': '❌',
                'connecting': '🔄',
                'reconnecting': '🔄',
                'failed': '💥'
            }[status] || '❓';
            
            log(`${emoji} Connection: ${status}`, details);
        }
    };

    // Channel operation logging
    const logChannel = (operation, channelName, details = null) => {
        if (isLoggingEnabled()) {
            log(`📡 Channel ${operation}: ${channelName}`, details);
        }
    };

    // Event logging
    const logEvent = (eventName, channelName, data = null) => {
        if (isLoggingEnabled()) {
            log(`📨 Event received: ${eventName} on ${channelName}`, data);
        }
    };

    // Toggle logging
    const toggleLogging = () => {
        const current = localStorage.getItem('ws_logging') === 'true';
        localStorage.setItem('ws_logging', (!current).toString());
        return !current;
    };

    // Get current logging status
    const getLoggingStatus = () => {
        return isLoggingEnabled();
    };

    return {
        log,
        warn,
        error,
        logConnection,
        logChannel,
        logEvent,
        toggleLogging,
        getLoggingStatus,
        isLoggingEnabled
    };
}
