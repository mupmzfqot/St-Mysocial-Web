<script setup>
import {Head, Link, router, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {ChevronRight, Search, Trash2, RotateCcw, AlertCircle} from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ref, watch} from "vue";
import {debounce} from "lodash";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    users: Array,
    pagination: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

const showDeleteModal = ref(false);
const selectedUser = ref(null);
const deleteForm = useForm({
    confirm: false
});

const reactivateForm = useForm({});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusBadge = (user) => {
    if (user.deleted_at) {
        return {
            label: 'Deleted',
            class: 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-500'
        };
    } else if (user.deletion_requested_at) {
        return {
            label: 'Deletion Requested',
            class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-500'
        };
    } else if (user.scheduled_deletion_at) {
        return {
            label: 'Scheduled',
            class: 'bg-orange-100 text-orange-800 dark:bg-orange-500/10 dark:text-orange-500'
        };
    }
    return {
        label: 'Unknown',
        class: 'bg-gray-100 text-gray-800 dark:bg-gray-500/10 dark:text-gray-500'
    };
};

const applyFilters = () => {
    const params = {};
    if (statusFilter.value) params.status = statusFilter.value;
    if (search.value) params.search = search.value;
    
    router.get(route('admin.account-removal.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(
    [statusFilter],
    () => applyFilters()
);

watch(
    search,
    debounce(() => applyFilters(), 500)
);

const openDeleteModal = (user) => {
    selectedUser.value = user;
    showDeleteModal.value = true;
    deleteForm.reset();
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedUser.value = null;
    deleteForm.reset();
};

const handlePermanentDelete = () => {
    if (!selectedUser.value) return;
    
    deleteForm.confirm = true;
    deleteForm.delete(route('admin.account-removal.permanent-delete', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
        onError: () => {
            // Error handling
        }
    });
};

const handleReactivate = (userId) => {
    if (!confirm('Are you sure you want to reactivate this account? All data will be restored.')) {
        return;
    }
    
    reactivateForm.post(route('admin.account-removal.reactivate', userId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Account Removal" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Account Removal
            </li>
        </Breadcrumbs>

        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 w-full inline-block align-middle">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                        <!-- Header -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                            <!-- Filters -->
                            <div class="flex flex-wrap gap-3 items-center">
                                <!-- Search -->
                                <div class="relative">
                                    <input
                                        type="text"
                                        v-model="search"
                                        class="py-2 px-3 ps-11 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                        placeholder="Search by name or email..."
                                    >
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-4">
                                        <Search class="shrink-0 size-4 text-gray-400" />
                                    </div>
                                </div>

                                <!-- Status Filter -->
                                <select
                                    v-model="statusFilter"
                                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400"
                                >
                                    <option value="">All Status</option>
                                    <option value="deleted">Deleted</option>
                                    <option value="deletion_requested">Deletion Requested</option>
                                    <option value="scheduled">Scheduled</option>
                                </select>
                            </div>
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        No.
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        User
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Status
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Deleted At
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Deletion Requested At
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Scheduled Deletion At
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Deletion Reason
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-center">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Actions
                                    </span>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            <tr v-for="(user, index) in users" :key="user.id" class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ pagination.from + index }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <div class="flex items-center gap-x-3">
                                            <img :src="user.avatar" class="size-10 shrink-0 rounded-full object-cover" alt="avatar">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ user.name }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-neutral-500">{{ user.email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span :class="['py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium rounded-full', getStatusBadge(user).class]">
                                            {{ getStatusBadge(user).label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ formatDate(user.deleted_at) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ formatDate(user.deletion_requested_at) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ formatDate(user.scheduled_deletion_at) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px max-w-xs">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400 truncate block" :title="user.deletion_reason || 'N/A'">
                                            {{ user.deletion_reason ? (user.deletion_reason.length > 50 ? user.deletion_reason.substring(0, 50) + '...' : user.deletion_reason) : 'N/A' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap text-center">
                                    <div class="p-6">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Reactivate Button -->
                                            <button
                                                v-if="user.can_reactivate || user.deleted_at"
                                                @click="handleReactivate(user.id)"
                                                :disabled="reactivateForm.processing"
                                                class="inline-flex items-center gap-x-2 py-1.5 px-2 text-sm font-medium text-green-800 hover:bg-green-100 rounded-lg disabled:opacity-50 disabled:pointer-events-none dark:text-green-400 dark:hover:bg-green-500/10"
                                            >
                                                <RotateCcw class="shrink-0 size-4" />
                                                Reactivate
                                            </button>
                                            
                                            <!-- Permanent Delete Button -->
                                            <button
                                                @click="openDeleteModal(user)"
                                                class="inline-flex items-center gap-x-2 py-1.5 px-2 text-sm font-medium text-red-800 hover:bg-red-100 rounded-lg dark:text-red-400 dark:hover:bg-red-500/10"
                                            >
                                                <Trash2 class="shrink-0 size-4" />
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <AlertCircle class="size-12 text-gray-400 mb-4" />
                                        <p class="text-sm text-gray-500 dark:text-neutral-400">No users found</p>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <!-- End Table -->

                        <!-- Footer -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <Pagination :links="pagination.links" />
                            <p class="text-sm text-gray-500 dark:text-neutral-500">
                                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
                            </p>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDeleteModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-neutral-800">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-neutral-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <Trash2 class="h-6 w-6 text-red-600" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Permanently Delete User
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-neutral-400">
                                        Are you sure you want to permanently delete <strong>{{ selectedUser?.name }}</strong>? This action cannot be undone and will delete all associated data including posts, comments, and media files.
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="deleteForm.confirm"
                                            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                                        >
                                        <span class="ml-2 text-sm text-gray-700 dark:text-neutral-300">
                                            I understand this action is permanent and cannot be undone
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-neutral-700">
                        <button
                            type="button"
                            @click="handlePermanentDelete"
                            :disabled="!deleteForm.confirm || deleteForm.processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ deleteForm.processing ? 'Deleting...' : 'Delete Permanently' }}
                        </button>
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-neutral-800 dark:text-white dark:border-neutral-600 dark:hover:bg-neutral-700"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>

