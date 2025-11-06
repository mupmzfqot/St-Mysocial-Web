<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, router } from '@inertiajs/vue3';
import { nextTick, ref, computed } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot } from '@headlessui/vue';
import { AlertTriangle, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    deletionStatus: {
        type: Object,
        default: null
    }
});

const confirmingDeletion = ref(false);
const confirmingReactivate = ref(false);
const isReactivating = ref(false);
const passwordInput = ref(null);
const reasonInput = ref(null);

const form = useForm({
    password: '',
    reason: '',
});

const isDeletionRequested = computed(() => {
    return props.deletionStatus?.account_status === 'deletion_requested';
});

const daysRemaining = computed(() => {
    const days = props.deletionStatus?.days_remaining ?? null;
    return days !== null ? Math.floor(days) : null;
});

const canReactivate = computed(() => {
    return props.deletionStatus?.can_reactivate ?? false;
});

const confirmDeletion = () => {
    confirmingDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const requestDeletion = (event) => {
    event?.preventDefault();
    
    if (form.processing) {
        return;
    }
    
    form.post(route('user-management.request-deletion'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            router.reload({ only: ['deletionStatus'] });
        },
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            if (!form.hasErrors()) {
                form.reset();
            }
        },
    });
};

const confirmReactivate = () => {
    confirmingReactivate.value = true;
};

const cancelDeletion = () => {
    if (isReactivating.value) {
        return;
    }
    
    isReactivating.value = true;
    
    router.post(route('user-management.cancel-deletion'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeReactivateModal();
            router.reload({ only: ['deletionStatus'] });
        },
        onError: () => {
            // Handle error if needed
        },
        onFinish: () => {
            isReactivating.value = false;
        },
    });
};

const closeReactivateModal = () => {
    confirmingReactivate.value = false;
};

const closeModal = () => {
    confirmingDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Delete Account</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Once your account deletion is requested, you will have 30 days to reactivate your account. 
                After 30 days, your account and all associated data will be permanently deleted. 
                Before requesting deletion, please download any data or information that you wish to retain.
            </p>
        </header>

        <!-- Deletion Status Alert -->
        <div v-if="isDeletionRequested" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-start">
                <AlertTriangle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3" />
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Account Deletion Requested
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p v-if="daysRemaining !== null && daysRemaining > 0">
                            Your account is scheduled for deletion in <strong>{{ daysRemaining }} days</strong>.
                        </p>
                        <p v-else-if="daysRemaining !== null && daysRemaining <= 0">
                            Your account will be deleted soon.
                        </p>
                        <p v-else>
                            Your account deletion has been requested.
                        </p>
                        <p class="mt-1">
                            You can reactivate your account by clicking the button below before the deletion date.
                        </p>
                    </div>
                    <div class="mt-4">
                        <SecondaryButton
                            v-if="canReactivate"
                            @click="confirmReactivate"
                            class="bg-green-600 hover:bg-green-700 text-white"
                        >
                            <CheckCircle class="h-4 w-4 mr-2 inline" />
                            Reactivate Account
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Deletion Button -->
        <DangerButton 
            v-if="!isDeletionRequested"
            @click="confirmDeletion"
            :disabled="confirmingDeletion"
        >
            Request Account Deletion
        </DangerButton>

        <!-- Confirmation Dialog -->
        <TransitionRoot appear :show="confirmingDeletion" as="template">
            <Dialog as="div" @close="closeModal" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
                        <DialogTitle class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Request Account Deletion
                        </DialogTitle>

                        <form @submit.prevent="requestDeletion">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Once you request account deletion, you will have <strong>30 days</strong> to reactivate your account. 
                                    After 30 days, your account and all associated data will be permanently deleted.
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    Please enter your password to confirm you would like to request account deletion.
                                </p>
                            </div>

                            <div class="mt-6">
                                <InputLabel for="password" value="Password" />
                                <TextInput
                                    id="password"
                                    ref="passwordInput"
                                    v-model="form.password"
                                    type="password"
                                    input-class="mt-1 block w-full"
                                    placeholder="Enter your password"
                                    autocomplete="new-password"
                                    @keyup.enter.prevent="requestDeletion"
                                />
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>

                            <div class="mt-6">
                                <InputLabel for="reason" value="Reason (Optional)" />
                                <textarea
                                    id="reason"
                                    ref="reasonInput"
                                    v-model="form.reason"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm"
                                    placeholder="Tell us why you're leaving (optional)"
                                    rows="3"
                                    maxlength="500"
                                    autocomplete="off"
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ form.reason?.length || 0 }}/500 characters
                                </p>
                                <InputError :message="form.errors.reason" class="mt-2" />
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <SecondaryButton type="button" @click="closeModal">
                                    Cancel
                                </SecondaryButton>

                                <DangerButton
                                    type="submit"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Request Deletion
                                </DangerButton>
                            </div>
                        </form>
                    </DialogPanel>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Reactivate Account Confirmation Dialog -->
        <TransitionRoot appear :show="confirmingReactivate" as="template">
            <Dialog as="div" @close="closeReactivateModal" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
                        <DialogTitle class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Reactivate Account
                        </DialogTitle>

                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Are you sure you want to reactivate your account?
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                All your data (posts, comments, likes, conversations) will be restored and your account will be active again.
                            </p>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton type="button" @click="closeReactivateModal">
                                Cancel
                            </SecondaryButton>

                            <SecondaryButton
                                type="button"
                                @click="cancelDeletion"
                                class="bg-green-600 hover:bg-green-700 text-white"
                                :disabled="isReactivating"
                            >
                                <CheckCircle class="h-4 w-4 mr-2 inline" />
                                {{ isReactivating ? 'Reactivating...' : 'Reactivate Account' }}
                            </SecondaryButton>
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>
        </TransitionRoot>
    </section>
</template>

