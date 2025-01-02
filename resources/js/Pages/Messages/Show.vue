<script setup>
import {Head, router} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {ref, onMounted, onBeforeUnmount, watch, nextTick} from 'vue';
import axios from "axios";

const props = defineProps({
    messages: Object,
    user: Object,
    conversation: Object,
});

const content = ref('');
const activeMessages = ref([...props.messages]);
const isFriendTyping = ref(false);
const messagesContainer = ref(null);
const isWebSocketConnected = ref(true);
const pollingInterval = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const fetchMessages = async () => {
    try {
        const response = await axios.get(route('message.fetch', props.conversation.id));
        const newMessages = response.data;
        
        // Only update if there are new messages
        if (newMessages.length > activeMessages.value.length) {
            activeMessages.value = newMessages;
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
};

const startPolling = () => {
    if (!pollingInterval.value) {
        pollingInterval.value = setInterval(fetchMessages, 3000); // Poll every 3 seconds
    }
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

const sendMessage = async (conversationId) => {
    if (content.value.trim() !== "") {
        try {
            const response = await axios.post(route('message.send', conversationId), {
                message: content.value,
            });
            
            content.value = "";
            
            // If WebSocket is down, immediately add the message to the list
            if (!isWebSocketConnected.value) {
                activeMessages.value.push(response.data);
            }
            
            scrollToBottom();
        } catch (error) {
            console.error('Error sending message:', error);
            // Handle error (you might want to show a notification to the user)
        }
    }
}

function textareaAutoHeight(el, offsetTop = 0) {
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight + offsetTop}px`;
}

const markAsRead = async() => {
    await axios.post(route('message.mark-as-read', props.conversation.id));
};

onMounted(() => {
    markAsRead();
    scrollToBottom();

    const channel = Echo.private(`conversation.${props.conversation.id}`);

    channel
        .listen('MessageSent', (event) => {
            activeMessages.value.push(event);
            scrollToBottom();
        })
        .listen('MessagesRead', (event) => {
            activeMessages.value = activeMessages.value.map(message => {
                if (!message.read_at && message.sender_id === $page.props.auth.user.id) {
                    return { ...message, read_at: new Date() };
                }
                return message;
            });
        });

    // Add connection status handlers using Laravel Echo's methods
    Echo.connector.pusher.connection.bind('connected', () => {
        isWebSocketConnected.value = true;
        stopPolling();
        console.log('WebSocket connected');
    });

    Echo.connector.pusher.connection.bind('disconnected', () => {
        isWebSocketConnected.value = false;
        startPolling();
        console.log('WebSocket disconnected, falling back to polling');
    });

    Echo.connector.pusher.connection.bind('error', () => {
        isWebSocketConnected.value = false;
        startPolling();
        console.log('WebSocket error, falling back to polling');
    });

    // Initial connection status check
    isWebSocketConnected.value = Echo.connector.pusher.connection.state === 'connected';
    if (!isWebSocketConnected.value) {
        startPolling();
    }

    // Cleanup connection handlers on component unmount
    onBeforeUnmount(() => {
        Echo.connector.pusher.connection.unbind('connected');
        Echo.connector.pusher.connection.unbind('disconnected');
        Echo.connector.pusher.connection.unbind('error');
        stopPolling();
    });

    const textareas = ['#hs-textarea-ex-1'];
    const cleanupFunctions = [];

    textareas.forEach((selector) => {
        const textarea = document.querySelector(selector);

        if (!textarea) return;

        const overlay = textarea.closest('.hs-overlay');

        const adjustHeight = () => textareaAutoHeight(textarea, 3);

        if (overlay) {
            const {element} = HSOverlay.getInstance(overlay, true);

            if (element) {
                element.on('open', adjustHeight);
                cleanupFunctions.push(() => element.off('open', adjustHeight));
            }
        } else {
            adjustHeight();
        }

        textarea.addEventListener('input', adjustHeight);
        cleanupFunctions.push(() => textarea.removeEventListener('input', adjustHeight));
    });

    onBeforeUnmount(() => {
        cleanupFunctions.forEach((cleanup) => cleanup());
    });
});


</script>

<template>
    <Head title="Messages"/>
    <HomeLayout>
        <div class="pb-3">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1 h-[calc(100vh-180px)]">
            <!-- Header -->
            <div class="shrink-0 group block p-3 bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full" :src="user.avatar" alt="Avatar">
                        </a>
                    </div>
                    <div class="ms-3">
                        <h3 class="font-semibold text-gray-800 dark:text-white">{{ user.name }}</h3>
                        <p v-if="isFriendTyping" class="text-sm -mt-1 text-gray-400 dark:text-neutral-500">
                            typing...
                        </p>
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto py-3 px-4 flex flex-col-reverse">
                <!-- Chat Bubble -->
                <ul class="space-y-2" v-if="activeMessages.length > 0" id="messagesContainer">
                    <li :class="['max-w-md flex gap-x-2 sm:gap-x-4', message.sender_id === $page.props.auth.user.id ? 'justify-end ms-auto' : '']"
                        v-for="message in activeMessages" :key="message.id">
                        <div class="bg-blue-600 rounded-2xl px-4 py-2 space-y-3" v-if="message.sender_id === $page.props.auth.user.id">
                            <p class="text-sm text-white" v-html="message.content"></p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 space-y-3" v-else>
                            <p class="text-sm text-gray-800" v-html="message.content"></p>
                        </div>
                    </li>
                </ul>

                <div v-else class="text-sm text-gray-400 dark:text-white py-3">
                    <p>No message.</p>
                </div>
            </div>

            <!-- Input Area -->
            <div class="shrink-0 border-t mt-auto">
                <!-- Textarea -->
                <div class="relative">
                    <textarea id="hs-textarea-ex-1" @input="sendTypingEvent" v-model="content" 
                        class="p-4 pb-12 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 
                        focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 
                        dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 
                        dark:focus:ring-neutral-600 max-h-32"
                        placeholder="Send message..."></textarea>

                    <!-- Toolbar -->
                    <div class="absolute bottom-px inset-x-px p-2 rounded-b-md bg-white dark:bg-neutral-900">
                        <!-- Send Button -->
                        <button type="button"
                                class="rounded-lg bg-blue-600 text-sm text-white hover:bg-blue-500 px-4 py-1"
                                @click="sendMessage(conversation.id)">
                            Send
                        </button>
                        <!-- End Send Button -->
                    </div>
                    <!-- End Toolbar -->
                </div>
                <!-- End Textarea -->
            </div>

        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
