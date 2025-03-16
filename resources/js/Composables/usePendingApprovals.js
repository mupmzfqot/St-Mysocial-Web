import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function usePendingApprovals() {
    const page = usePage();
    const pendingApprovals = computed(() => {
        const approvals = page.props.pendingApprovals || { users: 0, posts: 0 };
        return {
            pendingUsers: approvals.users || 0,
            pendingPosts: approvals.posts || 0
        };
    });

    const fetchPendingApprovals = async () => {
        try {
            await router.reload({
                only: ['pendingApprovals'],
                preserveState: true
            });
        } catch (error) {
            console.error('Error reloading pending approvals:', error);
        }
    };



    return {
        pendingApprovals,
        fetchPendingApprovals
    };
}
