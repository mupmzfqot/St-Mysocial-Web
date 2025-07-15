import { ref, onMounted, onBeforeUnmount } from 'vue';

export function useWebSocket() {
    const isConnected = ref(false);
    const connectionAttempts = ref(0);
    const maxReconnectAttempts = 5;
    const reconnectDelay = 3000;
    const channels = ref(new Map());
    const reconnectTimer = ref(null);
    const echoReady = ref(false);
    const hasAttemptedConnection = ref(false);

    // Connection status tracking
    const connectionStatus = ref('disconnected'); // 'connecting', 'connected', 'disconnected', 'reconnecting', 'failed'

    // Error tracking
    const lastError = ref(null);
    const errorCount = ref(0);

    // Wait for Echo to be properly initialized
    const waitForEcho = (maxAttempts = 20, interval = 100) => {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            
            const checkEcho = () => {
                attempts++;
                
                // Check if Echo exists and has proper structure
                if (typeof window !== 'undefined' && 
                    window.Echo && 
                    window.Echo.connector && 
                    window.Echo.connector.socket &&
                    window.Echo.connector.socket.connected !== undefined) {
                    
                    echoReady.value = true;
                    console.log('✅ Echo is ready');
                    resolve();
                } else if (attempts >= maxAttempts) {
                    const error = new Error('Echo initialization timeout after ' + maxAttempts + ' attempts');
                    console.error('❌ Echo initialization failed:', error);
                    connectionStatus.value = 'failed';
                    reject(error);
                } else {
                    setTimeout(checkEcho, interval);
                }
            };
            
            checkEcho();
        });
    };

    // Connection management
    const connect = async () => {
        if (connectionStatus.value === 'connecting' || connectionStatus.value === 'connected') {
            return;
        }

        hasAttemptedConnection.value = true;
        connectionStatus.value = 'connecting';
        
        try {
            // Wait for Echo to be properly initialized
            await waitForEcho();
            
            // Set up connection event listeners
            window.Echo.connector.socket.on('connect', () => {
                console.log('✅ WebSocket connected');
                isConnected.value = true;
                connectionStatus.value = 'connected';
                connectionAttempts.value = 0;
                errorCount.value = 0;
                lastError.value = null;
            });

            window.Echo.connector.socket.on('disconnect', (reason) => {
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

            window.Echo.connector.socket.on('connect_error', (error) => {
                console.error('🔌 WebSocket connection error:', error);
                lastError.value = error;
                errorCount.value++;
                connectionStatus.value = 'failed';
            });

        } catch (error) {
            console.error('❌ WebSocket setup failed:', error);
            lastError.value = error;
            connectionStatus.value = 'failed';
        }
    };

    // Reconnection logic
    const scheduleReconnect = () => {
        if (reconnectTimer.value) {
            clearTimeout(reconnectTimer.value);
        }

        connectionAttempts.value++;
        connectionStatus.value = 'reconnecting';
        
        console.log(`🔄 Attempting to reconnect (${connectionAttempts.value}/${maxReconnectAttempts})...`);
        
        reconnectTimer.value = setTimeout(() => {
            connect();
        }, reconnectDelay * connectionAttempts.value);
    };

    // Channel management with improved waiting
    const subscribeToChannel = async (channelName, eventName, callback, errorCallback = null) => {
        try {
            // Wait for Echo to be ready
            if (!echoReady.value) {
                await waitForEcho();
            }

            // Double-check Echo is available
            if (typeof window === 'undefined' || !window.Echo) {
                throw new Error('Echo not available for channel subscription');
            }

            const channel = window.Echo.private(channelName);
            
            if (!channel) {
                throw new Error(`Failed to create channel: ${channelName}`);
            }
            
            channel
                .listen(eventName, callback)
                .error((error) => {
                    console.error(`❌ Channel error for ${channelName}:`, error);
                    lastError.value = error;
                    
                    if (errorCallback) {
                        errorCallback(error);
                    }
                });

            // Store channel reference
            channels.value.set(channelName, channel);
            
            console.log(`✅ Subscribed to channel: ${channelName}`);
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

    // Unsubscribe from channel
    const unsubscribeFromChannel = (channelName) => {
        const channel = channels.value.get(channelName);
        if (channel) {
            try {
                channel.unsubscribe();
                channels.value.delete(channelName);
                console.log(`✅ Unsubscribed from channel: ${channelName}`);
            } catch (error) {
                console.error(`❌ Error unsubscribing from channel ${channelName}:`, error);
            }
        }
    };

    // Cleanup all channels
    const cleanupChannels = () => {
        channels.value.forEach((channel, channelName) => {
            try {
                channel.unsubscribe();
                console.log(`✅ Cleaned up channel: ${channelName}`);
            } catch (error) {
                console.error(`❌ Error cleaning up channel ${channelName}:`, error);
            }
        });
        channels.value.clear();
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
        };
    };

    // Manual reconnection
    const forceReconnect = () => {
        console.log('🔄 Force reconnecting...');
        connectionAttempts.value = 0;
        echoReady.value = false;
        hasAttemptedConnection.value = false;
        cleanupChannels();
        connect();
    };

    // Lifecycle hooks
    onMounted(() => {
        // Wait for DOM to be ready, then try to connect
        setTimeout(() => {
            connect();
        }, 1000); // Increased delay to ensure Echo is loaded
    });

    onBeforeUnmount(() => {
        if (reconnectTimer.value) {
            clearTimeout(reconnectTimer.value);
            reconnectTimer.value = null;
        }
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
        
        // Methods
        connect,
        subscribeToChannel,
        unsubscribeFromChannel,
        cleanupChannels,
        checkConnection,
        forceReconnect,
        waitForEcho,
    };
} 