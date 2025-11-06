<script setup>
import {Head, Link, router, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {ChevronRight, AlertTriangle, User, FileText, Calendar, CheckCircle, X} from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ref, computed} from "vue";
import {Dialog, DialogPanel, TransitionRoot, TransitionChild} from "@headlessui/vue";

const props = defineProps({
    report: Object,
    statuses: Array,
});

const form = useForm({
    status: props.report.status,
    admin_notes: props.report.admin_notes || '',
});

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-500',
        'reviewed': 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-500',
        'resolved': 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-500',
        'dismissed': 'bg-gray-100 text-gray-800 dark:bg-gray-500/10 dark:text-gray-500',
    };
    return classes[status] || classes.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pending',
        'reviewed': 'Reviewed',
        'resolved': 'Resolved',
        'dismissed': 'Dismissed',
    };
    return labels[status] || status;
};

const getReasonLabel = (reason) => {
    const labels = {
        'spam': 'Spam',
        'harassment': 'Harassment',
        'inappropriate_content': 'Inappropriate Content',
        'fake_account': 'Fake Account',
        'copyright_violation': 'Copyright Violation',
        'other': 'Other',
    };
    return labels[reason] || reason;
};

const submit = () => {
    form.post(route('admin.reports.update', props.report.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        },
    });
};

const showMediaModal = ref(false);
const selectedMedia = ref([]);
const selectedMediaIndex = ref(0);

const openMediaModal = (media, index = 0) => {
    selectedMedia.value = media;
    selectedMediaIndex.value = index;
    showMediaModal.value = true;
};

const closeMediaModal = () => {
    showMediaModal.value = false;
    selectedMedia.value = [];
    selectedMediaIndex.value = 0;
};
</script>

<template>
    <Head title="Report Detail" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center">
                <Link :href="route('admin.reports.index')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                    Reports
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Report Detail
            </li>
        </Breadcrumbs>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Report Information -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-neutral-200">Report Information</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Reporter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Reporter
                            </label>
                            <div class="flex items-center gap-x-3">
                                <img :src="report.reporter?.avatar" class="size-10 shrink-0 rounded-full object-cover" alt="avatar">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ report.reporter?.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-neutral-500">{{ report.reporter?.email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Report Type
                            </label>
                            <p class="text-sm text-gray-800 dark:text-neutral-200">{{ report.type }}</p>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Reason
                            </label>
                            <p class="text-sm text-gray-800 dark:text-neutral-200">{{ getReasonLabel(report.reason) }}</p>
                        </div>

                        <!-- Description -->
                        <div v-if="report.description">
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Description
                            </label>
                            <p class="text-sm text-gray-800 dark:text-neutral-200 whitespace-pre-wrap">{{ report.description }}</p>
                        </div>

                        <!-- Reported Item -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Reported {{ report.type }}
                            </label>
                            <div v-if="report.reported_item" class="bg-gray-50 dark:bg-neutral-800 rounded-lg p-4">
                                <div v-if="report.type === 'Post'">
                                    <div class="flex items-center gap-x-3 mb-3">
                                        <img :src="report.reported_item.author?.avatar" class="size-8 shrink-0 rounded-full object-cover" alt="avatar">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ report.reported_item.author?.name }}</p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-800 dark:text-neutral-200 whitespace-pre-wrap mb-3" v-html="report.reported_item.content"></p>
                                    
                                    <!-- Media Display -->
                                    <div v-if="report.reported_item.media && report.reported_item.media.length > 0" class="mt-4 grid grid-cols-2 gap-2">
                                        <div v-for="(mediaItem, index) in report.reported_item.media" :key="mediaItem.id" class="relative">
                                            <img v-if="!mediaItem.is_video && mediaItem.original_url"
                                                :src="mediaItem.original_url"
                                                :alt="mediaItem.filename"
                                                class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-90"
                                                @click="openMediaModal(report.reported_item.media, index)"
                                            />
                                            <div v-else-if="mediaItem.is_video" class="relative">
                                                <video
                                                    :src="mediaItem.url"
                                                    class="w-full h-32 object-cover rounded-lg"
                                                    controls
                                                ></video>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="report.type === 'User'">
                                    <div class="flex items-center gap-x-3">
                                        <img :src="report.reported_item.avatar" class="size-10 shrink-0 rounded-full object-cover" alt="avatar">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ report.reported_item.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-neutral-500">{{ report.reported_item.email }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-400">Reported item not available</p>
                        </div>

                        <!-- Created At -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Reported At
                            </label>
                            <p class="text-sm text-gray-800 dark:text-neutral-200">
                                {{ new Date(report.created_at).toLocaleString() }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status Update Form -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-neutral-200">Update Status</h2>
                    </div>
                    <form @submit.prevent="submit" class="p-6 space-y-4">
                        <!-- Current Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Current Status
                            </label>
                            <span :class="['py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium rounded-full', getStatusBadgeClass(report.status)]">
                                {{ getStatusLabel(report.status) }}
                            </span>
                        </div>

                        <!-- Status Select -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                New Status <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="w-full py-2 px-3 border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400"
                                required
                            >
                                <option v-for="status in statuses" :key="status" :value="status">
                                    {{ getStatusLabel(status) }}
                                </option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
                        </div>

                        <!-- Admin Notes -->
                        <div>
                            <label for="admin_notes" class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Admin Notes
                            </label>
                            <textarea
                                id="admin_notes"
                                v-model="form.admin_notes"
                                rows="6"
                                maxlength="2000"
                                class="w-full py-2 px-3 border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400"
                                placeholder="Add notes about this report..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ form.admin_notes?.length || 0 }}/2000 characters
                            </p>
                            <p v-if="form.errors.admin_notes" class="mt-1 text-xs text-red-600">{{ form.errors.admin_notes }}</p>
                        </div>

                        <!-- Reviewer Info -->
                        <div v-if="report.reviewer">
                            <label class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2">
                                Reviewed By
                            </label>
                            <p class="text-sm text-gray-800 dark:text-neutral-200">{{ report.reviewer.name }}</p>
                            <p v-if="report.reviewed_at" class="text-xs text-gray-500 dark:text-neutral-500">
                                {{ new Date(report.reviewed_at).toLocaleString() }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-2 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
                            >
                                <CheckCircle class="shrink-0 size-4" />
                                {{ form.processing ? 'Updating...' : 'Update Status' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Previous Admin Notes -->
                <div v-if="report.admin_notes" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-neutral-200">Previous Admin Notes</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-800 dark:text-neutral-200 whitespace-pre-wrap">{{ report.admin_notes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Modal -->
        <TransitionRoot appear :show="showMediaModal" as="template">
            <Dialog as="div" @close="closeMediaModal" class="relative z-50">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black/75" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-4xl transform overflow-hidden rounded-lg bg-white dark:bg-neutral-800 shadow-xl transition-all">
                                <div class="relative">
                                    <button
                                        @click="closeMediaModal"
                                        class="absolute top-4 right-4 z-10 p-2 bg-black/50 rounded-full text-white hover:bg-black/70"
                                    >
                                        <X class="size-5" />
                                    </button>
                                    
                                    <div v-if="selectedMedia.length > 0" class="relative">
                                        <img v-if="!selectedMedia[selectedMediaIndex]?.is_video && selectedMedia[selectedMediaIndex]?.original_url"
                                            :src="selectedMedia[selectedMediaIndex].original_url"
                                            :alt="selectedMedia[selectedMediaIndex].filename"
                                            class="w-full max-h-[80vh] object-contain"
                                        />
                                        <video v-else-if="selectedMedia[selectedMediaIndex]?.is_video"
                                            :src="selectedMedia[selectedMediaIndex].url"
                                            class="w-full max-h-[80vh]"
                                            controls
                                            autoplay
                                        ></video>
                                        
                                        <!-- Navigation arrows -->
                                        <div v-if="selectedMedia.length > 1" class="absolute inset-y-0 left-0 flex items-center">
                                            <button
                                                @click="selectedMediaIndex = (selectedMediaIndex - 1 + selectedMedia.length) % selectedMedia.length"
                                                class="p-2 bg-black/50 rounded-r-lg text-white hover:bg-black/70"
                                            >
                                                ←
                                            </button>
                                        </div>
                                        <div v-if="selectedMedia.length > 1" class="absolute inset-y-0 right-0 flex items-center">
                                            <button
                                                @click="selectedMediaIndex = (selectedMediaIndex + 1) % selectedMedia.length"
                                                class="p-2 bg-black/50 rounded-l-lg text-white hover:bg-black/70"
                                            >
                                                →
                                            </button>
                                        </div>
                                        
                                        <!-- Thumbnails -->
                                        <div v-if="selectedMedia.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2">
                                            <button
                                                v-for="(item, index) in selectedMedia"
                                                :key="item.id"
                                                @click="selectedMediaIndex = index"
                                                :class="[
                                                    'w-16 h-16 rounded overflow-hidden border-2',
                                                    index === selectedMediaIndex ? 'border-blue-500' : 'border-transparent opacity-60 hover:opacity-100'
                                                ]"
                                            >
                                                <img v-if="!item.is_video && item.original_url"
                                                    :src="item.original_url"
                                                    :alt="item.filename"
                                                    class="w-full h-full object-cover"
                                                />
                                                <div v-else class="w-full h-full bg-gray-800 flex items-center justify-center text-white text-xs">
                                                    Video
                                                </div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>

