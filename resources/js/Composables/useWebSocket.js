import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

export function useWebSocket() {
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
                console.log('✅ WebSocket connected');
                isConnected.value = true;
                connectionStatus.value = 'connected';
                connectionAttempts.value = 0;
                errorCount.value = 0;
                lastError.value = null;
            });

            // Connection lost
            socket.on('disconnect', (reason) => {
                console.log('❌ WebSocket disconnected:', reason);
                isConnected.value = false;
                connectionStatus.value = 'disconnected';
                
                // Attempt reconnection
                if (connectionAttempts.value < maxReconnectAttempts) {
                    scheduleReconnect();
                } else {
                    connectionStatus.value = 'failed';
                }
            });

            // Connection error
            socket.on('connect_error', (error) => {
                console.error('🔌 WebSocket connection error:', error);
                lastError.value = error;
                errorCount.value++;
                connectionStatus.value = 'failed';
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
        
        try {
            // Wait for Echo to be ready
            await waitForEcho();
            
            // Check if already connected
            if (getConnectionState() === 'connected') {
                isConnected.value = true;
                connectionStatus.value = 'connected';
                return;
            }
            
            // Setup connection listeners
            if (!setupConnectionListeners()) {
                throw new Error('Failed to setup connection listeners');
            }
            
        } catch (error) {
            console.error('❌ WebSocket setup failed:', error);
            lastError.value = error;
            connectionStatus.value = 'failed';
        }
    };

    // Schedule reconnection
    const scheduleReconnect = () => {
        if (reconnectTimer.value) {
            clearTimeout(reconnectTimer.value);
        }

        connectionAttempts.value++;
        connectionStatus.value = 'reconnecting';
        
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

            console.log(`🔌 Subscribing to channel: ${channelName} for event: ${eventName}`);

            // Create private channel
            const channel = window.Echo.private(channelName);
            
            if (!channel) {
                throw new Error(`Failed to create channel: ${channelName}`);
            }
            
            // OPTIMIZED EVENT LISTENING - Try multiple event name formats
            const eventNames = [
                eventName, // Original event name (e.g., 'MessageSent')
                `App\\Events\\${eventName}`, // Full namespace
                eventName.toLowerCase().replace(/([a-z])([A-Z])/g, '$1-$2'), // kebab-case (e.g., 'message-sent')
                eventName.replace(/([A-Z])/g, '_$1').toLowerCase(), // snake_case (e.g., 'message_sent')
            ];

            console.log(`🎯 Trying event names:`, eventNames);

            // Listen for all possible event name formats
            eventNames.forEach(name => {
                channel.listen(name, (data) => {
                    console.log(`📨 Received event ${name} on channel ${channelName}:`, data);
                    console.log(`📨 Data type:`, typeof data);
                    console.log(`📨 Data keys:`, data ? Object.keys(data) : 'null');
                    
                    // Ensure data is properly structured
                    if (data && typeof data === 'object') {
                        callback(data);
                    } else {
                        console.warn(`⚠️ Received invalid data for event ${name}:`, data);
                        console.warn(`⚠️ Data type:`, typeof data);
                    }
                });
            });

            // Also listen for any event on the channel (fallback)
            channel.listen('.', (data) => {
                console.log(`📨 Received any event on channel ${channelName}:`, data);
                if (data && typeof data === 'object') {
                    callback(data);
                }
            });

            // Handle channel errors
            channel.error((error) => {
                console.error(`❌ Channel error for ${channelName}:`, error);
                lastError.value = error;
                
                if (errorCallback) {
                    errorCallback(error);
                }
            });

            // Store channel reference
            channels.value.set(channelName, channel);
            
            console.log(`✅ Successfully subscribed to channel: ${channelName}`);
            return channel;
            
        } catch (error) {
            console.error(`❌ Failed to subscribe to channel ${channelName}:`, error);
            lastError.value = error;
            
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
                console.log(`✅ Successfully unsubscribed from channel: ${channelName}`);
            } catch (error) {
                console.error(`❌ Error unsubscribing from channel ${channelName}:`, error);
            }
        }
    };

    // Cleanup all channels
    const cleanupChannels = () => {
        console.log('🧹 Cleaning up all channels...');
        channels.value.forEach((channel, channelName) => {
            try {
                window.Echo.leave(channelName);
            } catch (error) {
                console.error(`❌ Error cleaning up channel ${channelName}:`, error);
            }
        });
        channels.value.clear();
    };

    // Force reconnection
    const forceReconnect = () => {
        connectionAttempts.value = 0;
        echoReady.value = false;
        hasAttemptedConnection.value = false;
        cleanupChannels();
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
                    console.log('✅ WebSocket connection detected during monitoring');
                    isConnected.value = true;
                    connectionStatus.value = 'connected';
                    connectionAttempts.value = 0;
                    errorCount.value = 0;
                    lastError.value = null;
                } else if (currentState === 'disconnected' && isConnected.value) {
                    console.log('❌ WebSocket disconnection detected during monitoring');
                    isConnected.value = false;
                    connectionStatus.value = 'disconnected';
                }
            }
        }, 3000); // Check every 3 seconds
    };

    // Lifecycle hooks
    onMounted(async () => {
        console.log('🚀 useWebSocket mounted, initializing...');
        
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
        console.log('🧹 useWebSocket unmounting, cleaning up...');
        
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
    };
}