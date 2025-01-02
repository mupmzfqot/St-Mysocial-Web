import { ref } from 'vue';

const unreadMessageCount = ref(0);
const unreadConversations = ref([]);

export function useUnreadMessages() {
    const fetchUnreadMessageCount = async () => {
        try {
            const response = await fetch(route('message.unread-count'));
            const data = await response.json();
            unreadMessageCount.value = data.total;
            unreadConversations.value = data.conversations;
        } catch (error) {
            console.error('Error fetching unread message count:', error);
        }
    };

    const getUnreadCountForConversation = (conversationId) => {
        const conversation = unreadConversations.value.find(c => c.id === conversationId);
        return conversation ? conversation.messages_count : 0;
    };

    return {
        unreadMessageCount,
        unreadConversations,
        fetchUnreadMessageCount,
        getUnreadCountForConversation
    };
}
