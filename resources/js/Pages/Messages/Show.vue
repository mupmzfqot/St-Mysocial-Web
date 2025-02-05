<script setup>
import {Head, Link, useForm} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {ref, onMounted, onBeforeUnmount, watch, nextTick} from 'vue';
import {useUnreadMessages} from "@/Composables/useUnreadMessages.js";
import {Paperclip, SmilePlus, X} from "lucide-vue-next";
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';
import PostMedia from "@/Components/PostMedia.vue";

const props = defineProps({
    messages: Object,
    user: Object,
    conversation: Object,
    config: {
        type: Object,
        default: () => ({
            showLikeCount: true,
            showUserAvatar: true,
            allowFileUpload: true
        })
    },
});

const form = useForm({
    message: '',
    post_id: props.postId,
    files: []
});

const fileInput = ref(null);
const previews = ref([]);
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
        const formData = new FormData();
        formData.append('message', content.value);
        form.files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });
        const response = await axios.post(route('message.send', conversationId), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        handleNewMessage(response.data);
        content.value = "";
        form.reset();
        previews.value = [];
        form.files = [];
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

const triggerFileInput = () => {
    fileInput.value.click();
};

const handleFiles = (event) => {
    const files = event.target?.files;
    if (files) {
        Array.from(files).forEach((file) => {
            if (file.size <= 5 * 1024 * 1024) {
                const fileReader = new FileReader();
                fileReader.onload = (e) => {
                    previews.value.push({
                        url: e.target.result,
                        type: file.type,
                        name: file.name,
                        file: file
                    });
                };
                form.files.push(file);
                fileReader.readAsDataURL(file);
            } else {
                alert('File is too large. Maximum size is 5MB.');
            }
        });
    }
    if (event.target) {
        event.target.value = '';
    }
};

const removeMedia = (index) => {
    previews.value.splice(index, 1);
    form.files.splice(index, 1);
};

</script>

<template>
    <Head title="Messages"/>
    <HomeLayout>
        <div class="pb-3 flex justify-between items-center">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
            <Link :href="route('message.index')" type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-gray-200 text-gray-800 hover:bg-gray-400-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                Back
            </Link>
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
                        <div v-if="message.sender_id === $page.props.auth.user.id" class="bg-blue-600 px-2 pb-2 rounded-lg">
                            <PostMedia
                                v-if="message.media && message.media.length > 0"
                                :medias="message.media"
                                :small="true"
                            />
                            <p class="text-sm text-white mt-1" v-html="message.content"></p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl px-2 pb-2" v-else>
                            <PostMedia
                                v-if="message.media && message.media.length > 0"
                                :medias="message.media"
                                :small="true"
                            />
                            <p class="text-sm text-gray-800" v-html="message.content"></p>
                        </div>
                    </li>
                </ul>

                <div v-else class="text-sm text-gray-400 dark:text-white py-3">
                    <p>No message.</p>
                </div>
            </div>

            <div class="flex items-center space-x-1 border-t rounded-xl border-gray-200 p-1 bg-gray-200">
                <!-- Chat Input -->
                <textarea
                    id="chat-input"
                    v-model="content"
                    contenteditable="true"
                    rows="1"
                    placeholder="Type a message..."
                    class="flex-1 py-2 px-3 text-sm border rounded-lg border-gray-300 bg-white focus:ring-1 focus:ring-blue-500 focus:outline-none break-words resize-none overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full"
                ></textarea>

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

                <!-- File Upload -->
                <div v-if="config.allowFileUpload" class="flex items-center">
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFiles"
                        multiple
                        accept="image/*,video/*,application/pdf"
                        class="hidden"
                    />
                    <button
                        @click="triggerFileInput"
                        class="flex items-center text-gray-600 hover:text-blue-600"
                    >
                        <Paperclip class="w-5 h-5 mr-1" />
                    </button>

                    <!-- Submit Button -->
                    <button
                        @click="sendMessage(conversation.id)"
                        :disabled="form.processing"
                        class="px-3 py-1 bg-gray-200 text-sm font-medium border-gray-400 border text-gray-800 rounded-xl hover:text-blue-600 hover:font-bold disabled:opacity-50"
                    >
                        {{ form.processing ? 'Sending...' : 'Send' }}
                    </button>
                </div>

            </div>
            <!-- File Previews -->
            <div v-if="previews.length > 0" class="flex bg-gray-100 py-2 flex-wrap gap-2 mt-2 ps-2">
                <div
                    v-for="(preview, index) in previews"
                    :key="index"
                    class="relative"
                >
                    <img v-if="preview.type === 'application/pdf'"
                         src="../../../images/pdf-icon.svg"
                         class="w-20 h-20 object-cover rounded-lg" alt=""
                    />
                    <img v-else
                        :src="preview.url"
                        class="w-20 h-20 object-cover rounded-lg" alt=""
                    />
                    <button
                        @click="removeMedia(index)"
                        class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
