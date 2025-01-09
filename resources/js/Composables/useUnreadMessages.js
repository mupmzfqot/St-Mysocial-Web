import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useUnreadMessages() {
    const page = usePage();

    const unreadMessageCount = computed(() => 
        page.props.unreadCount?.total || 0
    );

    const unreadConversations = computed(() => 
        page.props.unreadCount?.conversations || []
    );

    const fetchUnreadMessageCount = () => {
        // Manually trigger a refresh of unread messages
        router.reload({ 
            only: ['unreadCount'],
            preserveState: true 
        });
    };

    const getUnreadCountForConversation = (conversationId) => {
        const conversation = unreadConversations.value.find(c => c.id === conversationId);
        return conversation ? conversation.messages_count : 0;
    };

    const markConversationAsRead = (conversationId) => {
        router.post(route('message.mark-as-read', conversationId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Trigger a refresh of unread messages after marking as read
                fetchUnreadMessageCount();
            }
        });
    };

    return {
        unreadMessageCount,
        unreadConversations,
        fetchUnreadMessageCount,
        getUnreadCountForConversation,
        markConversationAsRead
    };
}