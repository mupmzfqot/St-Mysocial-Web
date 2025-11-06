<script setup>
import {Head, Link, router} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {ChevronRight, Search, AlertTriangle, Eye} from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ref, watch, computed} from "vue";
import {debounce} from "lodash";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    reports: Object,
    filters: Object,
    statuses: Array,
    reasons: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const reasonFilter = ref(props.filters?.reason || '');
const typeFilter = ref(props.filters?.type || '');

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

const applyFilters = () => {
    const params = {};
    if (statusFilter.value) params.status = statusFilter.value;
    if (reasonFilter.value) params.reason = reasonFilter.value;
    if (typeFilter.value) params.type = typeFilter.value;
    if (search.value) params.search = search.value;
    
    router.get(route('admin.reports.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(
    [statusFilter, reasonFilter, typeFilter],
    () => applyFilters()
);

watch(
    search,
    debounce(() => applyFilters(), 500)
);
</script>

<template>
    <Head title="Reports" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Reports
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
                                        placeholder="Search..."
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
                                    <option v-for="status in statuses" :key="status" :value="status">
                                        {{ getStatusLabel(status) }}
                                    </option>
                                </select>

                                <!-- Reason Filter -->
                                <select
                                    v-model="reasonFilter"
                                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400"
                                >
                                    <option value="">All Reasons</option>
                                    <option v-for="reason in reasons" :key="reason" :value="reason">
                                        {{ getReasonLabel(reason) }}
                                    </option>
                                </select>

                                <!-- Type Filter -->
                                <select
                                    v-model="typeFilter"
                                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400"
                                >
                                    <option value="">All Types</option>
                                    <option value="Post">Post</option>
                                    <option value="User">User</option>
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
                                        Reporter
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Type
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Reason
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Status
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Reported Item
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Created At
                                    </span>
                                </th>
                                <th scope="col" class="px-6 py-3 text-center">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                        Action
                                    </span>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            <tr v-for="(report, index) in reports.data" :key="report.id" class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ reports.from + index }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <div class="flex items-center gap-x-3">
                                            <img :src="report.reporter?.avatar" class="size-8 shrink-0 rounded-full object-cover" alt="avatar">
                                            <div>
                                                <span class="block text-sm font-semibold text-gray-800 dark:text-neutral-200">{{ report.reporter?.name }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-neutral-500">{{ report.reporter?.email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">{{ report.type }}</span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">{{ getReasonLabel(report.reason) }}</span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span :class="['py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium rounded-full', getStatusBadgeClass(report.status)]">
                                            {{ getStatusLabel(report.status) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap max-w-xs">
                                    <div class="p-6">
                                        <div v-if="report.reported_item">
                                            <div v-if="report.type === 'Post'" class="text-sm text-gray-600 dark:text-neutral-400">
                                                <div class="flex items-center gap-x-2 mb-1">
                                                    <span class="font-semibold">{{ report.reported_item.author?.name }}</span>
                                                </div>
                                                <p class="truncate" v-html="report.reported_item.content?.substring(0, 50) + '...'"></p>
                                                <!-- Media thumbnails -->
                                                <div v-if="report.reported_item.media && report.reported_item.media.length > 0" class="flex gap-1 mt-2">
                                                    <template v-for="(mediaItem, index) in report.reported_item.media.slice(0, 3)" :key="mediaItem.id">
                                                        <img v-if="!mediaItem.is_video && mediaItem.original_url"
                                                            :src="mediaItem.original_url"
                                                            :alt="mediaItem.filename"
                                                            class="w-12 h-12 object-cover rounded"
                                                        />
                                                    </template>
                                                    <div v-if="report.reported_item.media.length > 3" class="w-12 h-12 bg-gray-200 dark:bg-neutral-700 rounded flex items-center justify-center text-xs">
                                                        +{{ report.reported_item.media.length - 3 }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else-if="report.type === 'User'" class="text-sm text-gray-600 dark:text-neutral-400">
                                                <span class="font-semibold">{{ report.reported_item.name }}</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-sm text-gray-400">N/A</span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap">
                                    <div class="p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">
                                            {{ new Date(report.created_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap text-center">
                                    <div class="p-6">
                                        <Link
                                            :href="route('admin.reports.show', report.id)"
                                            class="inline-flex items-center gap-x-2 py-1.5 px-2 text-sm font-medium text-gray-800 hover:bg-gray-100 rounded-lg dark:text-neutral-400 dark:hover:bg-neutral-700"
                                        >
                                            <Eye class="shrink-0 size-4" />
                                            View
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="reports.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <AlertTriangle class="size-12 text-gray-400 mb-4" />
                                        <p class="text-sm text-gray-500 dark:text-neutral-400">No reports found</p>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <!-- End Table -->

                        <!-- Footer -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <Pagination :links="reports.links" />
                            <p class="text-sm text-gray-500 dark:text-neutral-500">
                                Showing {{ reports.from }} to {{ reports.to }} of {{ reports.total }} results
                            </p>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->
    </AuthenticatedLayout>
</template>

<style scoped>

</style>

