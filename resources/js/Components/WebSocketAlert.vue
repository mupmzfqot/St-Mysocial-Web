<template>
    <div v-if="showAlert" class="fixed bottom-4 left-4 z-50">
        <div :class="[
            'px-3 py-2 rounded-lg text-xs shadow-lg flex items-center gap-2',
            connectionStatus === 'disconnected' ? 'bg-red-500 text-white' : 
            connectionStatus === 'connecting' ? 'bg-yellow-500 text-white' :
            connectionStatus === 'reconnecting' ? 'bg-orange-500 text-white' :
            connectionStatus === 'failed' ? 'bg-red-600 text-white' :
            'bg-green-500 text-white'
        ]">
            <div :class="[
                'w-2 h-2 rounded-full',
                connectionStatus === 'disconnected' ? 'bg-white animate-pulse' :
                connectionStatus === 'connecting' ? 'bg-white animate-spin' :
                connectionStatus === 'reconnecting' ? 'bg-white animate-pulse' :
                connectionStatus === 'failed' ? 'bg-white' :
                'bg-white'
            ]"></div>
            <span>
                {{ getStatusMessage() }}
            </span>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    showAlert: {
        type: Boolean,
        default: false
    },
    connectionStatus: {
        type: String,
        default: 'disconnected'
    }
});

const getStatusMessage = () => {
    switch (props.connectionStatus) {
        case 'disconnected':
            return 'WebSocket disconnected - using HTTP mode';
        case 'connecting':
            return 'Connecting to WebSocket...';
        case 'reconnecting':
            return 'Reconnecting to WebSocket...';
        case 'failed':
            return 'WebSocket connection failed - using HTTP mode';
        case 'connected':
            return 'WebSocket connected';
        default:
            return 'WebSocket status unknown';
    }
};
</script> 