<script setup>
import {Head, Link, useForm, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import {ref, onMounted, onBeforeUnmount, watch, nextTick} from 'vue';
import {useUnreadMessages} from "@/Composables/useUnreadMessages.js";
import {useWebSocket} from "@/Composables/useWebSocket.js";
import {Paperclip, SmilePlus, X, LinkIcon} from "lucide-vue-next";
import EmojiPicker from 'vue3-emoji-picker';
import 'vue3-emoji-picker/css';
import PostMedia from "@/Components/PostMedia.vue";
import QuillEditor from '@/Components/QuillEditor.vue';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot} from "@headlessui/vue";

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

const $page = usePage();

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
const isPolling = ref(false);
const pollingInterval = ref(null);
const quillEditor = ref(null);
const showLinkModal = ref(false);
const linkUrl = ref('');
const selectedRange = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const handleNewMessage = (message) => {
    console.log('📨 Handling new message:', message);
    console.log('📨 Message type:', typeof message);
    console.log('📨 Message keys:', message ? Object.keys(message) : 'null');
    
    // Ensure message is an object and has required properties
    if (typeof message !== 'object' || message === null) {
        console.error('❌ Invalid message object:', message);
        return;
    }
    
    try {
        // Create a completely new object to avoid reference issues
        const safeMessage = {
            id: parseInt(message.id) || Date.now(),
            content: String(message.content || ''),
            sender_id: parseInt(message.sender_id) || $page.props.auth.user.id,
            sender_name: String(message.sender_name || $page.props.auth.user.name),
            conversation_id: parseInt(message.conversation_id) || props.conversation.id,
            created_at: message.created_at || new Date().toISOString(),
            media: Array.isArray(message.media) ? message.media : []
        };
        
        console.log('✅ Adding safe message to active messages:', safeMessage);
        activeMessages.value.push(safeMessage);
        scrollToBottom();
    } catch (error) {
        console.error('❌ Error in handleNewMessage:', error);
        console.error('❌ Message that caused error:', message);
        console.error('❌ Error stack:', error.stack);
    }
};

const sendMessage = async (conversationId) => {
    let content = quillEditor.value.getContent();
    let contentValue = content.replace(/<\/?[^>]+(>|$)/g, "");
    if (contentValue.trim() === '' && form.files.length === 0) return;

    try {
        let message = contentValue.trim() === '' ? '' : content;
        const formData = new FormData();
        formData.append('message', message);
        form.files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        console.log('🚀 Sending message to:', route('message.send', conversationId));
        console.log('📝 Message content:', message);
        
        const response = await axios.post(route('message.send', conversationId), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        console.log('📨 Raw response:', response);
        console.log('📨 Response data:', response.data);
        console.log('📨 Response status:', response.status);

        // Check if there's an error in the response
        if (response.data && response.data.error === 1) {
            return;
        }
        
        // Check if response indicates success with data
        if (response.data && response.data.message === 'Message sent successfully' && response.data.data) {
            console.log('✅ Message sent successfully with data:', response.data.data);
            // Use the actual message data from the response
            handleNewMessage(response.data.data);
            
            // Clear form and return early
            if (quillEditor.value.getContent()) {
                quillEditor.value.setContent('');
            }
            form.reset();
            previews.value = [];
            form.files = [];
            return;
        }

        // If we reach here, it means we don't have proper data
        console.log('⚠️ No proper data in response, clearing form only');
        
        if (quillEditor.value.getContent()) {
            quillEditor.value.setContent('');
        }
        

    } catch (error) {
        console.error('❌ Error sending message:', error);
        console.error('❌ Error details:', {
            message: error.message,
            stack: error.stack,
            response: error.response?.data
        });
        
        // Only show alert if it's a network or server error
        if (error.response?.status >= 400) {
            alert('Failed to send message. Please try again.');
        }
    }
};

const { isConnected, subscribeToChannel, unsubscribeFromChannel, activeChannels } = useWebSocket();

const detectEventName = () => {
    // Try to detect the correct event name by checking the backend event class
    const eventNames = [
        'MessageSent', // Standard Laravel Echo format
        'App\\Events\\MessageSent', // Full namespace
        'message-sent', // kebab-case
        'message_sent', // snake_case
        'MessageSentEvent', // Alternative
        'NewMessage', // Alternative event name
        'message.created', // Laravel convention
        'message.sent' // Alternative convention
    ];
    
    console.log('🎯 Available event names to try:', eventNames);
    return eventNames;
};

const setupWebSocket = async () => {
    const channelName = `conversation.${props.conversation.id}`;

    const messageCallback = (event) => {
        console.log('📨 Received WebSocket message:', event);
        console.log('📨 Event type:', typeof event);
        console.log('📨 Event keys:', event ? Object.keys(event) : 'null');
        
        // Ensure event is a valid message object
        if (typeof event !== 'object' || event === null) {
            console.error('❌ Invalid WebSocket event:', event);
            return;
        }
        
        // Handle different possible event structures
        let messageData = event;
        
        // If event has a 'data' property, use that
        if (event.data && typeof event.data === 'object') {
            messageData = event.data;
        }
        
        // Additional validation for required fields
        if (!messageData.id || !messageData.content) {
            console.error('❌ WebSocket event missing required fields:', messageData);
            return;
        }
        
        // Check if this message is already in the list
        const existingMessage = activeMessages.value.find(msg => msg.id === messageData.id);
        if (!existingMessage) {
            console.log('✅ Adding new message from WebSocket:', messageData);
            handleNewMessage(messageData);
        } else {
            console.log('⚠️ Message already exists, skipping:', messageData.id);
        }
    };

    const errorCallback = (error) => {
        console.error('❌ WebSocket channel error:', error);
        isWebSocketConnected.value = false;
        // Fallback to polling if WebSocket fails
        startPolling();
    };

    try {
        console.log('🔌 Setting up WebSocket for channel:', channelName);
        
        // OPTIMIZED: Use useWebSocket composable with multiple event name attempts
        const channel = await subscribeToChannel(channelName, 'MessageSent', messageCallback, errorCallback);

        if (channel) {
            console.log('✅ WebSocket setup successful via composable');
            isWebSocketConnected.value = true;
            stopPolling();
        } else {
            console.log('⚠️ Composable method failed, trying direct Echo...');
            
            // FALLBACK: Direct Echo subscription with multiple event names
            try {
                const directChannel = window.Echo.private(channelName);
                const eventNames = detectEventName();
                
                eventNames.forEach(eventName => {
                    directChannel.listen(eventName, (data) => {
                        console.log(`📨 Direct Echo received ${eventName}:`, data);
                        messageCallback(data);
                    });
                });
                
                // Also listen for any event
                directChannel.listen('.', (data) => {
                    console.log('📨 Direct Echo received any event:', data);
                    messageCallback(data);
                });
                
                directChannel.error((error) => {
                    console.error('❌ Direct Echo channel error:', error);
                    errorCallback(error);
                });
                
                console.log('✅ Direct Echo setup successful');
                isWebSocketConnected.value = true;
                stopPolling();
                
            } catch (directError) {
                console.error('❌ Direct Echo setup failed:', directError);
                isWebSocketConnected.value = false;
                startPolling();
            }
        }
        
        // Test the WebSocket connection
        console.log('🔍 Testing WebSocket connection...');
        setTimeout(() => {
            console.log('🔍 WebSocket connection status:', isConnected.value);
            console.log('🔍 Active channels:', activeChannels.value);
        }, 2000);
        
    } catch (error) {
        console.error('❌ Failed to setup WebSocket:', error);
        isWebSocketConnected.value = false;
        startPolling();
    }
};

const startPolling = () => {
    if (!pollingInterval.value) {
        isPolling.value = true;
        pollingInterval.value = setInterval(async () => {
            try {
                const response = await axios.get(route('message.show', props.conversation.id));
                const newMessages = response.data;

                // Check if there are new messages
                if (newMessages && newMessages.length > 0) {
                    // Find the latest message ID we have
                    const currentLatestId = activeMessages.value.length > 0 
                        ? Math.max(...activeMessages.value.map(msg => msg.id))
                        : 0;
                    
                    // Add only new messages
                    const newMessagesToAdd = newMessages.filter(msg => msg.id > currentLatestId);
                    
                    if (newMessagesToAdd.length > 0) {
                        activeMessages.value.push(...newMessagesToAdd);
                        scrollToBottom();
                    }
                }
            } catch (error) {
                console.error('❌ Polling error:', error);
            }
        }, 2000); // Reduced to 2 seconds for faster updates
    }
};

const stopPolling = () => {
    if (pollingInterval.value) {
        clearInterval(pollingInterval.value);
        pollingInterval.value = null;
        isPolling.value = false;
    }
};

const manualRefresh = async () => {
    try {
        const response = await axios.get(route('message.show', props.conversation.id));
        const newMessages = response.data;

        if (newMessages && newMessages.length > 0) {
            // Find the latest message ID we have
            const currentLatestId = activeMessages.value.length > 0 
                ? Math.max(...activeMessages.value.map(msg => msg.id))
                : 0;
            
            // Add only new messages
            const newMessagesToAdd = newMessages.filter(msg => msg.id > currentLatestId);
            
            if (newMessagesToAdd.length > 0) {
                activeMessages.value.push(...newMessagesToAdd);
                scrollToBottom();
            } 
        }
    } catch (error) {
        // console.error('❌ Manual refresh error:', error);
    }
};

const markAsReadOnInteraction = async () => {
    if (props.conversation && props.conversation.id) {
        markAsRead()
        fetchUnreadMessageCount();
    }
};

const setupInteractionListeners = () => {
    if (quillEditor.value) {
        const quillElement = quillEditor.value.$el;
        quillElement.addEventListener('click', markAsReadOnInteraction);
    }

    const messageContainer = document.querySelector('.message-container');
    if (messageContainer) {
        messageContainer.addEventListener('click', markAsReadOnInteraction);
    }
};

const testEventListening = () => {
    console.log('🧪 Manual Event Listening Test');
    
    const channelName = `conversation.${props.conversation.id}`;
    console.log('Testing channel:', channelName);
    
    if (!window.Echo) {
        console.error('❌ Echo is not available');
        return;
    }
    
    try {
        const testChannel = window.Echo.private(channelName);
        const eventNames = detectEventName();
        
        console.log('🎯 Setting up event listeners for:', eventNames);
        
        // Listen for all possible event names
        eventNames.forEach(eventName => {
            testChannel.listen(eventName, (data) => {
                console.log(`✅ SUCCESS: Received ${eventName} event:`, data);
                console.log('Event data structure:', {
                    hasId: !!data.id,
                    hasContent: !!data.content,
                    hasSenderId: !!data.sender_id,
                    keys: Object.keys(data)
                });
            });
        });
        
        // Listen for any event
        testChannel.listen('.', (data) => {
            console.log('✅ SUCCESS: Received any event:', data);
        });
        
        console.log('✅ Event listeners setup complete');
        console.log('📝 Now send a message to test if events are received');
        
    } catch (error) {
        console.error('❌ Event listening test failed:', error);
    }
};

// Add to window for manual testing
onMounted(() => {
    window.testEventListening = testEventListening;
    console.log('🔧 Event listening test available: window.testEventListening()');
});

onMounted(async () => {
    // Initialize WebSocket with delay to ensure Echo is ready
    setTimeout(async () => {
        await setupWebSocket();
    }, 1000);
    
    scrollToBottom();
    markAsRead();
    setupInteractionListeners();
});

onBeforeUnmount(() => {
    stopPolling();
    // Unsubscribe from the conversation channel
    unsubscribeFromChannel(`conversation.${props.conversation.id}`);
});

const { fetchUnreadMessageCount } = useUnreadMessages();
const markAsRead = async() => {
    await axios.post(route('message.mark-as-read', props.conversation.id));
    fetchUnreadMessageCount();
};

const showEmojiPicker = ref(false);
const onSelectEmoji = (emoji) => {
    if (quillEditor.value) {
        const range = quillEditor.value.getSelection();
        quillEditor.value.insertText(range ? range.index: 0, emoji.i);
    }
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

const openLinkDialog = () => {
    selectedRange.value = quillEditor.value.getSelection();
    if (selectedRange.value && selectedRange.value.length > 0) {
        linkText.value = quillEditor.value.getText(selectedRange.value.index, selectedRange.value.length);
    }
    showLinkModal.value = true;
};

const insertLink = () => {
    if (linkUrl.value) {
        const displayText = linkUrl.value;
        let finalUrl = linkUrl.value;
        if(linkUrl.value.includes('.') && !linkUrl.value.startsWith('http://') && !linkUrl.value.startsWith('https://')) {
            finalUrl = `https://${linkUrl.value}`;
        }

        if (selectedRange.value) {
            if (selectedRange.value.length > 0) {
                quillEditor.value.deleteText(selectedRange.value.index, selectedRange.value.length);
            }
            quillEditor.value.insertText(selectedRange.value.index, displayText);
            quillEditor.value.formatText(selectedRange.value.index, displayText.length, 'link', finalUrl);
        } else {
            const currentIndex = quillEditor.value.getLength() - 1;
            quillEditor.value.insertText(currentIndex, displayText);
            quillEditor.value.formatText(currentIndex, displayText.length, 'link', finalUrl);
        }
    }

    // Reset the form
    linkUrl.value = '';
    showLinkModal.value = false;
    selectedRange.value = null;
};

const styledTag = (value) => {
    // Handle null, undefined, or empty values
    if (!value || typeof value !== 'string') {
        return '';
    }
    
    return value.replace(/<a /g, '<a class="text-blue-600 hover:text-blue-800 hover:no-underline" target="_blank"')
        .replace(/<ul>/g, '<ul class="list-disc list-inside pl-4">')
        .replace(/<ol>/g, '<ol class="list-decimal list-inside pl-3.5">');
}

</script>

<template>
    <Head title="Messages"/>
    <HomeLayout>
        <div class="pb-3 flex justify-between items-center">
            <h1 class="font-semibold text-xl dark:text-white">Messages</h1>
            <div class="flex items-center gap-3">
                <!-- Connection Status Indicator -->
                <div v-if="!isWebSocketConnected && isPolling" class="flex items-center gap-2 text-sm text-yellow-600">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                    <span>Polling for messages...</span>
                    <button @click="manualRefresh" class="text-xs bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">
                        Refresh
                    </button>
                </div>
                <Link :href="route('message.index')" type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-gray-200 text-gray-800 hover:bg-gray-400-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                    Back
                </Link>
            </div>
        </div>

        <div class="flex flex-col bg-white border shadow-sm rounded-xl p-1 h-[calc(100vh-180px)] message-container">
            <!-- Header -->
            <div class="shrink-0 group block p-3 bg-gray-100 rounded-lg">
                <div class="flex items-center">
                    <div class="hs-tooltip inline-block">
                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                            <img class="inline-block size-[40px] rounded-full object-cover" :src="user.avatar" alt="Avatar">
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
                        <div v-if="message.sender_id === $page.props.auth.user.id" class="bg-blue-100 px-2 pb-2 rounded-lg">
                            <PostMedia
                                v-if="message.media && message.media.length > 0"
                                :medias="message.media"
                                :small="true"
                            />
                            <p class="text-sm mt-1" v-html="styledTag(message.content)"></p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl px-2 py-2" v-else>
                            <PostMedia
                                v-if="message.media && message.media.length > 0"
                                :medias="message.media"
                                :small="true"
                            />
                            <p class="text-sm text-gray-800" v-html="styledTag(message.content)"></p>
                        </div>
                    </li>
                </ul>

                <div v-else class="text-sm text-gray-400 dark:text-white py-3">
                    <p>No message.</p>
                </div>
            </div>

            <div class="flex items-center space-x-1 border-t rounded-xl border-gray-200 p-1 bg-gray-200">
                <!-- Chat Input -->
                    <QuillEditor ref="quillEditor" @update:value="content = $event"
                        class="flex-1 text-sm border rounded-lg border-gray-300 bg-white focus:ring-1 focus:ring-blue-500 focus:outline-none break-words resize-none overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full"
                    />

                <!-- Emoji Picker -->
                <div class="hs-tooltip [--placement:bottom] inline-block">
                    <button @click="showEmojiPicker = !showEmojiPicker"  type="button"
                            class="flex hs-tooltip-toggle items-center text-gray-800 hover:text-blue-600">
                        <SmilePlus class="shrink-0 size-5" />
                        <span
                            class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                            role="tooltip">
                                        Emoji
                                    </span>
                    </button>
                </div>

                <div class="relative">
                    <EmojiPicker
                        v-if="showEmojiPicker"
                        @select="onSelectEmoji"
                        class="z-50 absolute bottom-8 right-0"
                    />
                </div>

                <div class="hs-tooltip [--placement:bottom] inline-block">
                    <button @click="openLinkDialog"  type="button"
                            class="flex hs-tooltip-toggle items-center text-gray-800 hover:text-blue-600">
                        <LinkIcon class="shrink-0 size-5" />
                        <span
                            class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                            role="tooltip">
                                        Insert Link
                                    </span>
                    </button>
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
                    <div class="hs-tooltip [--placement:bottom] inline-block">
                        <button
                            @click="triggerFileInput"
                            class="flex hs-tooltip-toggle items-center text-gray-600 hover:text-blue-600"
                        >
                            <Paperclip class="w-5 h-5 mr-1" />
                            <span
                                class="hs-tooltip-content hs-tooltip-shown:opacity-100 hs-tooltip-shown:visible opacity-0 transition-opacity inline-block absolute invisible z-10 py-1 px-2 bg-gray-900 text-xs font-medium text-white rounded shadow-sm dark:bg-neutral-700"
                                role="tooltip">
                                        Insert File
                                    </span>
                        </button>
                    </div>

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

        <TransitionRoot appear :show="showLinkModal" as="template">
            <Dialog as="div" @close="showLinkModal = false" class="relative z-10">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                    Insert Link
                                </DialogTitle>
                                <div class="mt-4">
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">URL</label>
                                        <input
                                            type="url"
                                            v-model="linkUrl"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="https://example.com"
                                        />
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end space-x-2">
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                        @click="showLinkModal = false"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                        @click="insertLink"
                                    >
                                        Insert
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

    </HomeLayout>
</template>

<style scoped>

</style>
