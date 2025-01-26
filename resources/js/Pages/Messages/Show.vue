<script setup>
import {Head} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {ref, onMounted, onBeforeUnmount, watch, nextTick} from 'vue';
import {useUnreadMessages} from "@/Composables/useUnreadMessages.js";
import {SmilePlus} from "lucide-vue-next";
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';

const props = defineProps({
    messages: Object,
    user: Object,
    conversation: Object,
});

const content = ref('');
const activeMessages = ref([...props.messages]);
const messagesContainer = ref(null);
const isWebSocketConnected = ref(false);
const pollingInterval = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const handleNewMessage = (message) => {
    activeMessages.value.push(message);
    scrollToBottom();
};

const sendMessage = async (conversationId) => {
    if (!content.value.trim()) return;

    try {
        const response = await axios.post(route('message.send', conversationId), {
            message: content.value,
        });
        handleNewMessage(response.data);
        content.value = "";
    } catch (error) {
        console.error('Error sending message:', error);
    }
};

const setupWebSocket = () => {
    try {
        const channel = Echo.private(`conversation.${props.conversation.id}`);

        channel
            .listen('MessageSent', (event) => {
                if (!activeMessages.value.some(msg => msg.id === event.id)) {
                    handleNewMessage(event);
                }
            })
            .error((error) => {
                console.error('WebSocket connection error:', error);
                isWebSocketConnected.value = false;
                startPolling();
            });

        isWebSocketConnected.value = true;
    } catch (error) {
        console.error('WebSocket setup failed:', error);
        isWebSocketConnected.value = false;
        startPolling();
    }
};

const startPolling = () => {
    if (!pollingInterval.value) {
        pollingInterval.value = setInterval(async () => {
            try {
                const response = await axios.get(route('message.show', props.conversation.id));
                const newMessages = response.data;

                if (newMessages.length > activeMessages.value.length) {
                    activeMessages.value = newMessages;
                    scrollToBottom();
                }
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 3000);
    }
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
    }
};

onMounted(() => {
    setupWebSocket();
    scrollToBottom();
    markAsRead();
});

onBeforeUnmount(() => {
    stopPolling();
});

const showEmojiPicker = ref(false);
const { fetchUnreadMessageCount } = useUnreadMessages();
const markAsRead = async() => {
    await axios.post(route('message.mark-as-read', props.conversation.id));
    fetchUnreadMessageCount();
};

const onSelectEmoji = (emoji) => {
    content.value += emoji.i;
    showEmojiPicker.value = false;
};

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

            <div class="flex items-center space-x-2 border-t rounded-xl border-gray-200 p-1 bg-gray-200">
                <!-- Chat Input -->
                <input
                    id="chat-input"
                    v-model="content"
                    contenteditable="true"
                    class="flex-1 py-2 px-3 text-sm border rounded-lg border-gray-300 bg-white focus:ring-1 focus:ring-blue-500 focus:outline-none break-words"
                    placeholder="Type a message..."
                >

                <!-- Emoji Picker -->
                <button @click="showEmojiPicker = !showEmojiPicker"  type="button"
                        class="flex items-center text-gray-800 hover:text-blue-600">
                    <SmilePlus class="shrink-0 size-5" />
                </button>

                <div class="relative">
                    <EmojiPicker
                        v-if="showEmojiPicker"
                        @select="onSelectEmoji"
                        class="z-50 absolute bottom-8 right-0"
                    />
                </div>

                <button
                    @click="sendMessage(conversation.id)"
                    class="px-3 py-1 bg-gray-200 text-sm font-medium border-gray-400 border text-gray-800 rounded-xl hover:text-blue-600 hover:font-bold disabled:opacity-50"
                >
                    Send
                </button>

            </div>

<!--            &lt;!&ndash; Input Area &ndash;&gt;-->
<!--            <div class="shrink-0 border-t mt-auto">-->
<!--                &lt;!&ndash; Textarea &ndash;&gt;-->
<!--                <div class="relative">-->
<!--                    <textarea id="hs-textarea-ex-1" @input="sendTypingEvent" v-model="content"-->
<!--                        class="p-4 pb-12 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500-->
<!--                        focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900-->
<!--                        dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500-->
<!--                        dark:focus:ring-neutral-600 max-h-32"-->
<!--                        placeholder="Send message..."></textarea>-->

<!--                    &lt;!&ndash; Toolbar &ndash;&gt;-->
<!--                    <div class="absolute bottom-px inset-x-px p-2 rounded-b-md bg-white dark:bg-neutral-900">-->
<!--                        &lt;!&ndash; Send Button &ndash;&gt;-->
<!--                        <button type="button"-->
<!--                                class="rounded-lg bg-blue-600 text-sm text-white hover:bg-blue-500 px-4 py-1"-->
<!--                                @click="sendMessage(conversation.id)">-->
<!--                            Send-->
<!--                        </button>-->
<!--                        &lt;!&ndash; End Send Button &ndash;&gt;-->
<!--                    </div>-->
<!--                    &lt;!&ndash; End Toolbar &ndash;&gt;-->
<!--                </div>-->
<!--                &lt;!&ndash; End Textarea &ndash;&gt;-->
<!--            </div>-->

        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
