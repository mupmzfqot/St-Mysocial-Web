<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, computed } from 'vue';
import { AlertTriangle, CheckCircle, LogIn } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    deletionStatus: {
        type: Object,
        default: null
    },
    isLoggedIn: {
        type: Boolean,
        default: false
    }
});

const form = useForm({
    email: '',
    password: '',
});

const daysRemaining = computed(() => {
    const days = props.deletionStatus?.days_remaining ?? null;
    return days !== null ? Math.floor(days) : null;
});

const canReactivate = computed(() => {
    return props.deletionStatus?.can_reactivate ?? false;
});

const submit = () => {
    form.post(route('login'), {
        preserveScroll: true,
        onSuccess: () => {
            // After login, if user has deletion_requested status, they will be redirected here
            // and can reactivate
            router.reload({ only: ['deletionStatus', 'isLoggedIn'] });
        },
    });
};

const reactivateAccount = () => {
    router.post(route('user-management.cancel-deletion'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Will redirect to login page
        },
    });
};
</script>

<template>
    <Head title="Reactivate Account" />

    <GuestLayout>
        <div class="flex items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <!-- Account Deletion Info -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
                    <div class="flex items-start">
                        <AlertTriangle class="h-6 w-6 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="flex-1">
                            <h2 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200 mb-2">
                                Account Deletion Requested
                            </h2>
                            <div class="text-sm text-yellow-700 dark:text-yellow-300 space-y-2">
                                <p v-if="daysRemaining !== null && daysRemaining > 0">
                                    Your account is scheduled for deletion in <strong>{{ daysRemaining }} days</strong>.
                                </p>
                                <p v-else-if="daysRemaining !== null && daysRemaining <= 0">
                                    Your account will be deleted soon.
                                </p>
                                <p v-else>
                                    Your account deletion has been requested.
                                </p>
                                <p>
                                    You can reactivate your account by logging in and clicking the reactivate account.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reactivate Button (if logged in and can reactivate) -->
                <div v-if="isLoggedIn && canReactivate" class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Reactivate Your Account
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Click the button below to reactivate your account. All your data will be restored.
                    </p>
                    <button
                        @click="reactivateAccount"
                        class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                    >
                        <CheckCircle class="h-4 w-4 mr-2" />
                        Reactivate Account
                    </button>
                </div>

                <!-- Cannot Reactivate Message -->
                <div v-if="isLoggedIn && !canReactivate" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                    <div class="flex items-start">
                        <AlertTriangle class="h-6 w-6 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" />
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                                Cannot Reactivate
                            </h3>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                Your account deletion grace period has expired. Your account can no longer be reactivated.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

