import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

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

    // Check current connection status from Echo
    const checkCurrentConnectionStatus = () => {
        if (typeof window !== 'undefined' && window.Echo && window.Echo.connector) {
            const socket = window.Echo.connector.socket;
            if (socket) {
                // Check if socket is already connected
                if (socket.connected) {
                    isConnected.value = true;
                    connectionStatus.value = 'connected';
                    connectionAttempts.value = 0;
                    errorCount.value = 0;
                    lastError.value = null;
                    return true;
                }
            }
        }
        return false;
    };

    // Wait for Echo to be properly initialized
    const waitForEcho = (maxAttempts = 50, interval = 100) => {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            
            const checkEcho = () => {
                attempts++;
                
                // Check if Echo exists and has proper structure
                if (typeof window !== 'undefined' && 
                    window.Echo && 
                    window.Echo.connector) {
                    
                    echoReady.value = true;
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
            
            // Check if already connected
            if (checkCurrentConnectionStatus()) {
                return; // Already connected, no need to set up listeners
            }
            
            // Only set up event listeners if Echo is ready
            if (window.Echo && window.Echo.connector) {
                // For Reverb, we need to check if the socket exists
                const socket = window.Echo.connector.socket;
                
                if (socket) {
                    socket.on('connect', () => {
                        isConnected.value = true;
                        connectionStatus.value = 'connected';
                        connectionAttempts.value = 0;
                        errorCount.value = 0;
                        lastError.value = null;
                    });

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

                    socket.on('connect_error', (error) => {
                        console.error('🔌 WebSocket connection error:', error);
                        lastError.value = error;
                        errorCount.value++;
                        connectionStatus.value = 'failed';
                    });
                } else {
                    // Retry connection after a short delay
                    setTimeout(() => connect(), 1000);
                }
            } else {
                throw new Error('Echo not properly initialized');
            }

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
        connectionAttempts.value = 0;
        echoReady.value = false;
        hasAttemptedConnection.value = false;
        cleanupChannels();
        connect();
    };

    // Periodic connection check
    let connectionCheckInterval = null;

    const startConnectionCheck = () => {
        if (connectionCheckInterval) {
            clearInterval(connectionCheckInterval);
        }
        
        connectionCheckInterval = setInterval(() => {
            if (typeof window !== 'undefined' && window.Echo && window.Echo.connector) {
                const socket = window.Echo.connector.socket;
                if (socket && socket.connected && !isConnected.value) {
                    isConnected.value = true;
                    connectionStatus.value = 'connected';
                    connectionAttempts.value = 0;
                    errorCount.value = 0;
                    lastError.value = null;
                } else if (socket && !socket.connected && isConnected.value) {
                    console.log('❌ WebSocket disconnection detected during periodic check');
                    isConnected.value = false;
                    connectionStatus.value = 'disconnected';
                }
            }
        }, 2000); // Check every 2 seconds
    };

    // Lifecycle hooks - DELAYED to ensure echo.js is loaded first
    onMounted(() => {
        // Check immediately if Echo is already available and connected
        if (typeof window !== 'undefined' && window.Echo) {
            if (checkCurrentConnectionStatus()) {
                startConnectionCheck();
                return;
            }
        }
        
        // Wait much longer to ensure echo.js is fully loaded
        setTimeout(() => {
            connect();
            startConnectionCheck();
        }, 5000); // Increased delay to ensure echo.js is loaded
    });

    onBeforeUnmount(() => {
        if (reconnectTimer.value) {
            clearTimeout(reconnectTimer.value);
            reconnectTimer.value = null;
        }
        if (connectionCheckInterval) {
            clearInterval(connectionCheckInterval);
            connectionCheckInterval = null;
        }
        cleanupChannels();
    });

    // Computed properties
    const activeChannels = computed(() => channels.value.size);

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
        startConnectionCheck,
    };
} 