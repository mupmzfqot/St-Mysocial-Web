<script setup>
import {Head, Link, router, usePage} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import { MessageSquareMore, UserX, UserCheck, AlertTriangle } from "lucide-vue-next";
import PostContent from "@/Components/PostContent.vue";
import { ref, computed } from "vue";
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";

const page = usePage();

const props = defineProps({
    user: Object,
    totalPosts: Number,
    totalComments: Number,
    totalLikes: Number,
    requestUrl: {
        type: String,
        required: true,
    },
    isBlocked: {
        type: Boolean,
        default: false,
    },
    isBlockedBy: {
        type: Boolean,
        default: false,
    },
    isReported: {
        type: Boolean,
        default: false,
    }
})

const isBlocked = ref(props.isBlocked);
const isBlocking = ref(false);
const error = ref(null);
const showBlockConfirmDialog = ref(false);

// Report user state
const showReportModal = ref(false);
const reportReason = ref('');
const reportDescription = ref('');
const reportError = ref('');
const reportSuccess = ref(false);
const isUserReported = ref(props.isReported || false);

// Check if this is current user's own profile
const isOwnProfile = computed(() => {
    return page.props.auth?.user && page.props.auth.user.id === props.user.id;
});

// Check if user can interact (not blocked and not own profile)
const canInteract = computed(() => {
    return !isOwnProfile.value && !isBlocked.value && !props.isBlockedBy;
});

const showBlockDialog = () => {
    showBlockConfirmDialog.value = true;
};

const confirmBlockUser = () => {
    showBlockConfirmDialog.value = false;
    
    if (isBlocking.value) return;
    
    isBlocking.value = true;
    error.value = null;

    // Use web route for session-based auth
    router.post(route('user-management.block', props.user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isBlocked.value = true;
            error.value = null;
        },
        onError: (errors) => {
            error.value = errors.error || errors.message || 'Failed to block user';
        },
        onFinish: () => {
            isBlocking.value = false;
        }
    });
};

const unblockUser = () => {
    if (isBlocking.value) return;
    
    isBlocking.value = true;
    error.value = null;

    // Use web route for session-based auth
    router.post(route('user-management.unblock', props.user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isBlocked.value = false;
            error.value = null;
        },
        onError: (errors) => {
            error.value = errors.error || errors.message || 'Failed to unblock user';
        },
        onFinish: () => {
            isBlocking.value = false;
        }
    });
};

// Report user methods
const openReportModal = () => {
    if (isUserReported.value) {
        return;
    }
    reportReason.value = '';
    reportDescription.value = '';
    reportError.value = '';
    reportSuccess.value = false;
    showReportModal.value = true;
};

const submitReport = () => {
    if (!reportReason.value) {
        reportError.value = 'Please select a reason for reporting this user.';
        return;
    }

    reportError.value = '';
    reportSuccess.value = false;

    router.post(route('user-management.report-user', props.user.id), {
        reason: reportReason.value,
        description: reportDescription.value || null
    }, {
        preserveScroll: true,
        onSuccess: () => {
            reportSuccess.value = true;
            isUserReported.value = true;
            setTimeout(() => {
                closeReportModal();
            }, 2000);
        },
        onError: (errors) => {
            if (errors.error) {
                reportError.value = errors.error;
            } else if (errors.reason) {
                reportError.value = errors.reason[0];
            } else if (errors.description) {
                reportError.value = errors.description[0];
            } else {
                reportError.value = 'Failed to submit report. Please try again.';
            }
        }
    });
};

const closeReportModal = () => {
    showReportModal.value = false;
    reportReason.value = '';
    reportDescription.value = '';
    reportError.value = '';
    reportSuccess.value = false;
};

const reportReasons = [
    { value: 'spam', label: 'Spam' },
    { value: 'harassment', label: 'Harassment' },
    { value: 'inappropriate_content', label: 'Inappropriate Content' },
    { value: 'fake_account', label: 'Fake Account' },
    { value: 'copyright_violation', label: 'Copyright Violation' },
    { value: 'other', label: 'Other' }
];
</script>

<template>
    <Head title="Profile" />
    <HomeLayout>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Background -->
            <div class="h-40" :style="{ backgroundImage: `url(${user.cover_image})` }">
            </div>
            <!-- Profile Section -->
            <div class="p-4 text-center">
                <div class="relative w-24 h-24 mx-auto -mt-16 bg-white rounded-full border-4 border-white overflow-hidden">
                    <img :src="user.avatar" alt="Profile"
                         class="w-full h-full object-cover">
                </div>
                <h3 class="mt-2 font-semibold text-gray-800">{{ user.name }}</h3>
                <p class="text-gray-500 text-sm">{{ user.email }}</p>
                <!-- Stats -->
                <div class="flex justify-center items-center space-x-6 mt-2 text-gray-800 text-sm">
                    <div class="text-center">
                        <p class=""><b>{{ totalPosts }}</b> Posts</p>
                    </div>
                    <div class="text-center">
                        <p class=""><b>{{ totalLikes }}</b> Likes</p>
                    </div>
                    <div class="text-center">
                        <p class=""><b>{{ totalComments }}</b> Comments</p>
                    </div>
                </div>

                <div class="pt-5 pb-3">
                    <!-- Error message -->
                    <div v-if="error" class="text-red-600 text-sm text-center mb-2">
                        {{ error }}
                    </div>
                    
                    <!-- Blocked by user message -->
                    <div v-if="isBlockedBy" class="text-sm text-gray-500 italic text-center mb-2">
                        You have been blocked
                    </div>
                    
                    <!-- Action buttons inline -->
                    <div class="flex flex-wrap justify-center items-center gap-2">
                         <!-- Send Message button (only if not blocked) -->
                         <Link 
                            v-if="canInteract"
                            :href="route('message.show', user.id)" 
                            type="button" 
                            class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <MessageSquareMore class="shrink-0 size-4" /> Send Message
                        </Link>
                        
                        <!-- Block/Unblock button -->
                        <button 
                            v-if="!isOwnProfile && !isBlockedBy"
                            @click="isBlocked ? unblockUser() : showBlockDialog()"
                            :disabled="isBlocking"
                            :class="[
                                'py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border transition-colors disabled:opacity-50 disabled:pointer-events-none',
                                isBlocked 
                                    ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' 
                                    : 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100'
                            ]"
                        >
                            <UserX v-if="!isBlocked" class="shrink-0 size-4" />
                            <UserCheck v-else class="shrink-0 size-4" />
                            <span v-if="isBlocking">Processing...</span>
                            <span v-else>{{ isBlocked ? 'Unblock User' : 'Block User' }}</span>
                        </button>
                        
                        <!-- Report User button -->
                        <button 
                            v-if="!isOwnProfile && !isBlockedBy"
                            @click="isUserReported ? null : openReportModal()"
                            :disabled="isUserReported"
                            :class="[
                                'py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border transition-colors disabled:opacity-50 disabled:pointer-events-none',
                                isUserReported
                                    ? 'border-gray-300 bg-white text-gray-500 cursor-default' 
                                    : 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100'
                            ]"
                        >
                            <AlertTriangle class="shrink-0 size-4" />
                            <span>{{ isUserReported ? 'Reported' : 'Report User' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-4">
            <h2 class="font-medium mb-2">Recent Posts</h2>
            <PostContent :requestUrl="requestUrl" />
        </div>

        <!-- Block User Confirmation Dialog -->
        <TransitionRoot appear :show="showBlockConfirmDialog" as="template">
            <Dialog as="div" @close="showBlockConfirmDialog = false" class="relative z-50">
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
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0">
                                        <AlertTriangle class="h-6 w-6 text-red-600" />
                                    </div>
                                    <div class="flex-1">
                                        <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                                            Block User?
                                        </DialogTitle>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">
                                                Are you sure you want to block <strong>{{ user.name }}</strong>? 
                                                You won't be able to see their posts, comments, or send them messages. 
                                                They also won't be able to see your content or interact with you.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                        @click="showBlockConfirmDialog = false"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                        @click="confirmBlockUser"
                                    >
                                        Block User
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Report User Modal -->
        <TransitionRoot appear :show="showReportModal" as="template" style="position: absolute; z-index: 9999">
            <Dialog as="div" @close="closeReportModal" class="relative">
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
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-neutral-800 p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
                                    Report User
                                </DialogTitle>

                                <!-- Success Message -->
                                <div v-if="reportSuccess" class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                    <p class="text-sm text-green-800 dark:text-green-200">
                                        Report submitted successfully. Our team will review it.
                                    </p>
                                </div>

                                <!-- Error Message -->
                                <div v-if="reportError" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                    <p class="text-sm text-red-800 dark:text-red-200">
                                        {{ reportError }}
                                    </p>
                                </div>

                                <div class="mt-2">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                        Please select a reason for reporting <strong>{{ user.name }}</strong>. This helps us understand the issue better.
                                    </p>

                                    <!-- Reason Selection -->
                                    <div class="mb-4">
                                        <label for="report-reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Reason <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="report-reason"
                                            v-model="reportReason"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
                                        >
                                            <option value="">Select a reason...</option>
                                            <option v-for="reason in reportReasons" :key="reason.value" :value="reason.value">
                                                {{ reason.label }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-4">
                                        <label for="report-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Additional Details (Optional)
                                        </label>
                                        <textarea
                                            id="report-description"
                                            v-model="reportDescription"
                                            rows="4"
                                            maxlength="1000"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
                                            placeholder="Provide additional details about why you're reporting this user..."
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ reportDescription?.length || 0 }}/1000 characters
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end space-x-2">
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                        @click="closeReportModal"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-red-100 dark:bg-red-900/20 px-4 py-2 text-sm font-medium text-red-900 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-900/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                        @click="submitReport"
                                    >
                                        Submit Report
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
