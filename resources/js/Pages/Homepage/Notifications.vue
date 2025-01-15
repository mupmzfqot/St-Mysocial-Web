<script setup>
import {Head, router} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import moment from "moment";

defineProps({
    notifications: Object,
});

const readNotification = (item) => {
    // router.post(route('read-notification', item.id), {}, {preserveScroll: true})
    router.visit(route('read-notification', item.id), {
        preserveScroll: true,
        method: 'post',
        onSuccess: visit => {
            router.get(item.data.url)
        },
    })
}
</script>

<template>
    <Head title="Notifications" />

    <HomeLayout>
        <div class="bg-white border shadow-sm rounded-xl py-2 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <!-- Table -->
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead class="bg-gray-50 dark:bg-neutral-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-start" colspan="2">
                        <div class="flex items-center gap-x-2">
                            <span class="text-sm font-semibold tracking-wide text-gray-800 dark:text-neutral-200">
                              Notifications
                            </span>
                        </div>
                    </th>

                </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                <tr v-for="notification in notifications.data" key="user.id" class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                    <td class="size-px whitespace-nowrap align-top justify-between">
                        <a @click="readNotification(notification)" class="block p-2 hover:bg-blue-100 dark:bg-neutral-900 dark:hover:bg-neutral-800" href="#">
                            <span class="text-sm text-gray-800 dark:text-neutral-400">{{ notification.data.message }}</span>
                        </a>
                    </td>
                    <td class="size-px whitespace-nowrap text-end pe-3">
                        <span class="text-xs text-gray-400">
                            {{ moment(notification.created_at).fromNow() }}
                        </span>
                    </td>
                </tr>

                </tbody>
            </table>
            <!-- End Table -->

            <!-- Footer -->
            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                <Pagination :links="notifications.links" />
                <p class="text-sm text-gray-500 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                    Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                </p>
            </div>
            <!-- End Footer -->
        </div>

    </HomeLayout>
</template>

<style scoped>

</style>
