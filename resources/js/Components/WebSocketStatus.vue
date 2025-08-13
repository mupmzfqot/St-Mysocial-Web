<template>
    <div v-if="showStatus" class="fixed bottom-4 right-4 z-50">
        <!-- Disconnected Status -->
        <div v-if="!isConnected && connectionStatus !== 'connecting'" 
             class="bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2"
             @mouseenter="showDetails = true"
             @mouseleave="showDetails = false">
            <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
            <span class="text-sm font-medium">WebSocket Disconnected</span>
            <button 
                @click="forceReconnect" 
                class="ml-2 bg-red-600 hover:bg-red-700 px-2 py-1 rounded text-xs"
            >
                Reconnect
            </button>
        </div>

        <!-- Connecting Status -->
        <div v-else-if="connectionStatus === 'connecting'" 
             class="bg-yellow-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2"
             @mouseenter="showDetails = true"
             @mouseleave="showDetails = false">
            <div class="w-2 h-2 bg-white rounded-full animate-spin"></div>
            <span class="text-sm font-medium">Connecting...</span>
        </div>

        <!-- Connected Status (minimal) -->
        <div v-else-if="isConnected" 
             class="bg-green-500 text-white px-3 py-1 rounded-lg shadow-lg flex items-center space-x-2"
             @mouseenter="showDetails = true"
             @mouseleave="showDetails = false">
            <div class="w-2 h-2 bg-white rounded-full"></div>
            <span class="text-xs">WS Connected</span>
        </div>

        <!-- Connection Status Details (on hover) -->
        <div v-if="showDetails" class="absolute bottom-full right-0 mb-2 bg-gray-800 text-white p-3 rounded-lg text-xs max-w-sm">
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
                    <span>Echo Ready:</span>
                    <span :class="echoReady ? 'text-green-400' : 'text-red-400'">
                        {{ echoReady ? 'Yes' : 'No' }}
                    </span>
                </div>
            </div>
            
            <div v-if="lastError" class="mt-2 p-2 bg-red-900 rounded">
                <div class="font-semibold">Last Error:</div>
                <div class="break-all">{{ lastError.message || lastError }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useWebSocket } from '@/Composables/useWebSocket';

const showDetails = ref(false);
const showStatus = ref(true);

const {
    isConnected,
    connectionStatus,
    connectionAttempts,
    errorCount,
    lastError,
    activeChannels,
    echoReady,
    forceReconnect
} = useWebSocket();

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
        case 'failed':
            return 'text-red-400';
        default:
            return 'text-gray-400';
    }
};
</script> 