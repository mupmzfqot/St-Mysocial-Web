<template>
    <div v-if="showDebug" class="fixed top-4 right-4 z-50 bg-gray-800 text-white p-4 rounded-lg text-xs max-w-sm">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-semibold">WebSocket Debug</h3>
            <button @click="showDebug = false" class="text-gray-400 hover:text-white">×</button>
        </div>
        
        <div class="space-y-1">
            <div class="flex justify-between">
                <span>Status:</span>
                <span :class="getStatusColor()">{{ connectionStatus }}</span>
            </div>
            <div class="flex justify-between">
                <span>Connected:</span>
                <span :class="isConnected ? 'text-green-400' : 'text-red-400'">
                    {{ isConnected ? 'Yes' : 'No' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span>Echo Ready:</span>
                <span :class="echoReady ? 'text-green-400' : 'text-red-400'">
                    {{ echoReady ? 'Yes' : 'No' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span>Attempts:</span>
                <span>{{ connectionAttempts }}/5</span>
            </div>
            <div class="flex justify-between">
                <span>Errors:</span>
                <span class="text-red-400">{{ errorCount }}</span>
            </div>
            <div class="flex justify-between">
                <span>Channels:</span>
                <span>{{ activeChannels }}</span>
            </div>
            <div class="flex justify-between">
                <span>Attempted:</span>
                <span :class="hasAttemptedConnection ? 'text-green-400' : 'text-red-400'">
                    {{ hasAttemptedConnection ? 'Yes' : 'No' }}
                </span>
            </div>
        </div>
        
        <div v-if="lastError" class="mt-2 p-2 bg-red-900 rounded text-xs">
            <div class="font-semibold">Last Error:</div>
            <div class="break-all">{{ lastError.message || lastError }}</div>
        </div>
        
        <div class="mt-3 space-y-1">
            <button 
                @click="forceReconnect"
                class="w-full bg-blue-600 hover:bg-blue-700 px-2 py-1 rounded text-xs"
            >
                Force Reconnect
            </button>
            <button 
                @click="checkConnection"
                class="w-full bg-gray-600 hover:bg-gray-700 px-2 py-1 rounded text-xs"
            >
                Check Connection
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useWebSocket } from '@/Composables/useWebSocket';

const showDebug = ref(false);

const {
    isConnected,
    connectionStatus,
    connectionAttempts,
    errorCount,
    lastError,
    echoReady,
    activeChannels,
    hasAttemptedConnection,
    forceReconnect,
    checkConnection
} = useWebSocket();

// Show debug panel on double-click of 'D' key
let keyPressCount = 0;
let keyPressTimer = null;

document.addEventListener('keydown', (e) => {
    if (e.key === 'd' || e.key === 'D') {
        keyPressCount++;
        
        if (keyPressTimer) {
            clearTimeout(keyPressTimer);
        }
        
        keyPressTimer = setTimeout(() => {
            if (keyPressCount >= 2) {
                showDebug.value = !showDebug.value;
            }
            keyPressCount = 0;
        }, 300);
    }
});

const getStatusColor = () => {
    switch (connectionStatus.value) {
        case 'connected':
            return 'text-green-400';
        case 'connecting':
            return 'text-yellow-400';
        case 'reconnecting':
            return 'text-orange-400';
        case 'disconnected':
            return 'text-red-400';
        default:
            return 'text-gray-400';
    }
};
</script> 