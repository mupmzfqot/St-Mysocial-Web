import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useWebSocketLogger } from './useWebSocketLogger';

export function useWebSocket() {
    const logger = useWebSocketLogger();
    
    // Connection state
    const isConnected = ref(false);
    const connectionStatus = ref('disconnected'); // 'connecting', 'connected', 'disconnected', 'failed'
    const connectionAttempts = ref(0);
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000;
    
    // Error tracking
    const lastError = ref(null);
    const errorCount = ref(0);
    
    // Channel management
    const channels = ref(new Map());
    const activeChannels = computed(() => channels.value.size);
    
    // Timers
    const reconnectTimer = ref(null);
    const connectionCheckInterval = ref(null);
    
    // Echo readiness
    const echoReady = ref(false);
    const hasAttemptedConnection = ref(false);

    // Check if Echo is available and properly initialized
    const isEchoAvailable = () => {
        return typeof window !== 'undefined' && 
               window.Echo && 
               window.Echo.connector;
    };

    // Get connection state from Echo
    const getConnectionState = () => {
        if (!isEchoAvailable()) return 'disconnected';
        
        // For Reverb, check if socket exists and is connected
        if (window.Echo.connector.socket) {
            return window.Echo.connector.socket.connected ? 'connected' : 'disconnected';
        }
        
        return 'disconnected';
    };

    // Wait for Echo to be ready
    const waitForEcho = (maxAttempts = 50, interval = 100) => {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            
            const checkEcho = () => {
                attempts++;
                
                if (isEchoAvailable()) {
                    echoReady.value = true;
                    resolve();
                } else if (attempts >= maxAttempts) {
                    const error = new Error('Echo initialization timeout');
                    connectionStatus.value = 'failed';
                    reject(error);
                } else {
                    setTimeout(checkEcho, interval);
                }
            };
            
            checkEcho();
        });
    };

    // Setup Echo connection listeners
    const setupConnectionListeners = () => {
        if (!isEchoAvailable()) return false;
        
        // For Reverb, we need to handle connection differently
        if (window.Echo.connector.socket) {
            const socket = window.Echo.connector.socket;
            
            // Remove existing listeners to prevent duplicates
            socket.off('connect');
            socket.off('disconnect');
            socket.off('connect_error');
            
            // Connection established
            socket.on('connect', () => {
                isConnected.value = true;
                connectionStatus.value = 'connected';
                connectionAttempts.value = 0;
                errorCount.value = 0;
                lastError.value = null;
                logger.logConnection('connected');
            });

            // Connection lost
            socket.on('disconnect', (reason) => {
                isConnected.value = false;
                connectionStatus.value = 'disconnected';
                logger.logConnection('disconnected', { reason });
                
                // Attempt reconnection
                if (connectionAttempts.value < maxReconnectAttempts) {
                    scheduleReconnect();
                } else {
                    connectionStatus.value = 'failed';
                    logger.logConnection('failed', { reason: 'Max reconnection attempts reached' });
                }
            });

            // Connection error
            socket.on('connect_error', (error) => {
                lastError.value = error;
                errorCount.value++;
                connectionStatus.value = 'failed';
                logger.error('Connection error', error);
            });
        }
        
        return true;
    };

    // Initialize connection
    const connect = async () => {
        if (connectionStatus.value === 'connecting' || connectionStatus.value === 'connected') {
            return;
        }

        hasAttemptedConnection.value = true;
        connectionStatus.value = 'connecting';
        logger.logConnection('connecting');
        
        try {
            // Wait for Echo to be ready
            await waitForEcho();
            
            // Check if already connected
            if (getConnectionState() === 'connected') {
                isConnected.value = true;
                connectionStatus.value = 'connected';
                logger.logConnection('connected', { note: 'Already connected' });
                return;
            }
            
            // Setup connection listeners
            if (!setupConnectionListeners()) {
                throw new Error('Failed to setup connection listeners');
            }
            
        } catch (error) {
            lastError.value = error;
            connectionStatus.value = 'failed';
            logger.error('Connection setup failed', error);
        }
    };

    // Schedule reconnection
    const scheduleReconnect = () => {
        if (reconnectTimer.value) {
            clearTimeout(reconnectTimer.value);
        }

        connectionAttempts.value++;
        connectionStatus.value = 'reconnecting';
        logger.logConnection('reconnecting', { attempt: connectionAttempts.value });
        
        reconnectTimer.value = setTimeout(() => {
            connect();
        }, reconnectDelay * connectionAttempts.value);
    };

    // Subscribe to a channel - OPTIMIZED IMPLEMENTATION
    const subscribeToChannel = async (channelName, eventName, callback, errorCallback = null) => {
        try {
            // Wait for Echo to be ready
            if (!echoReady.value) {
                await waitForEcho();
            }

            // Check if Echo is available
            if (!isEchoAvailable()) {
                throw new Error('Echo not available for channel subscription');
            }

            // Create private channel
            const channel = window.Echo.private(channelName);
            
            if (!channel) {
                throw new Error(`Failed to create channel: ${channelName}`);
            }
            
            logger.logChannel('subscribed', channelName);
            
            // OPTIMIZED EVENT LISTENING - Try multiple event name formats
            const eventNames = [
                eventName, // Original event name (e.g., 'MessageSent')
                `App\\Events\\${eventName}`, // Full namespace
                eventName.toLowerCase().replace(/([a-z])([A-Z])/g, '$1-$2'), // kebab-case (e.g., 'message-sent')
                eventName.replace(/([A-Z])/g, '_$1').toLowerCase(), // snake_case (e.g., 'message_sent')
            ];

            // Listen for all possible event name formats
            eventNames.forEach(name => {
                channel.listen(name, (data) => {
                    // Ensure data is properly structured
                    if (data && typeof data === 'object') {
                        logger.logEvent(name, channelName, data);
                        callback(data);
                    }
                });
            });

            // Also listen for any event on the channel (fallback)
            channel.listen('.', (data) => {
                if (data && typeof data === 'object') {
                    logger.logEvent('wildcard', channelName, data);
                    callback(data);
                }
            });

            // Handle channel errors
            channel.error((error) => {
                lastError.value = error;
                logger.error(`Channel error for ${channelName}`, error);
                
                if (errorCallback) {
                    errorCallback(error);
                }
            });

            // Store channel reference
            channels.value.set(channelName, channel);
            return channel;
            
        } catch (error) {
            lastError.value = error;
            logger.error(`Failed to subscribe to channel ${channelName}`, error);
            
            if (errorCallback) {
                errorCallback(error);
            }
            
            return null;
        }
    };

    // Unsubscribe from a channel
    const unsubscribeFromChannel = (channelName) => {
        const channel = channels.value.get(channelName);
        if (channel) {
            try {
                // Properly unsubscribe from the channel
                window.Echo.leave(channelName);
                channels.value.delete(channelName);
                logger.logChannel('unsubscribed', channelName);
            } catch (error) {
                // Silent error handling for unsubscription
            }
        }
    };

    // Cleanup all channels
    const cleanupChannels = () => {
        channels.value.forEach((channel, channelName) => {
            try {
                window.Echo.leave(channelName);
            } catch (error) {
                // Silent error handling for cleanup
            }
        });
        channels.value.clear();
        logger.log('All channels cleaned up');
    };

    // Force reconnection
    const forceReconnect = () => {
        connectionAttempts.value = 0;
        echoReady.value = false;
        hasAttemptedConnection.value = false;
        cleanupChannels();
        logger.log('Force reconnection initiated');
        connect();
    };

    // Health check
    const checkConnection = () => {
        return {
            isConnected: isConnected.value,
            connectionStatus: connectionStatus.value,
            connectionAttempts: connectionAttempts.value,
            errorCount: errorCount.value,
            lastError: lastError.value,
            activeChannels: channels.value.size,
            echoReady: echoReady.value,
            hasAttemptedConnection: hasAttemptedConnection.value,
            connectionState: getConnectionState(),
        };
    };

    // Start periodic connection monitoring
    const startConnectionMonitoring = () => {
        if (connectionCheckInterval.value) {
            clearInterval(connectionCheckInterval.value);
        }
        
        connectionCheckInterval.value = setInterval(() => {
            if (isEchoAvailable()) {
                const currentState = getConnectionState();
                
                if (currentState === 'connected' && !isConnected.value) {
                    isConnected.value = true;
                    connectionStatus.value = 'connected';
                    connectionAttempts.value = 0;
                    errorCount.value = 0;
                    lastError.value = null;
                    logger.logConnection('connected', { note: 'Detected during monitoring' });
                } else if (currentState === 'disconnected' && isConnected.value) {
                    isConnected.value = false;
                    connectionStatus.value = 'disconnected';
                    logger.logConnection('disconnected', { note: 'Detected during monitoring' });
                }
            }
        }, 3000); // Check every 3 seconds
    };

    // Lifecycle hooks
    onMounted(async () => {
        // Check if Echo is already available and connected
        if (isEchoAvailable()) {
            const state = getConnectionState();
            if (state === 'connected') {
                isConnected.value = true;
                connectionStatus.value = 'connected';
                setupConnectionListeners();
                startConnectionMonitoring();
                return;
            }
        }
        
        // Initialize connection with delay to ensure echo.js is loaded
        setTimeout(async () => {
            await connect();
            startConnectionMonitoring();
        }, 2000);
    });

    onBeforeUnmount(() => {
        // Clear timers
        if (reconnectTimer.value) {
            clearTimeout(reconnectTimer.value);
            reconnectTimer.value = null;
        }
        
        if (connectionCheckInterval.value) {
            clearInterval(connectionCheckInterval.value);
            connectionCheckInterval.value = null;
        }
        
        // Cleanup channels
        cleanupChannels();
    });

    return {
        // State
        isConnected,
        connectionStatus,
        lastError,
        errorCount,
        echoReady,
        hasAttemptedConnection,
        activeChannels,
        connectionAttempts,
        
        // Methods
        connect,
        subscribeToChannel,
        unsubscribeFromChannel,
        cleanupChannels,
        checkConnection,
        forceReconnect,
        waitForEcho,
        startConnectionMonitoring,
        
        // Logger utility
        logger,
    };
}