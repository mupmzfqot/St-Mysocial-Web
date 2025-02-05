<script setup>
import {Head, Link, router} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {CheckCircle, ChevronRight, MinusCircle, Search, UserCircle} from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {reactive, ref, watch} from "vue";
import {debounce} from "lodash";
import Pagination from "@/Components/Pagination.vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

const props = defineProps({
    posts: Object,
    searchTerm: String
});

const search = ref(props.searchTerm);


watch(
    search, debounce(
        (q) => router.get(route('post-moderation.index'), { search: q }, { preserveState: true }), 500
    )
);

const confirmData = reactive({
    confirmId: ''
})

const changeStatus = (post, status) => {
    confirmData.id = post.id;
    confirmData.message = `Do you want to publish/unpublish this post?`;
    confirmData.url = route('post-moderation.update-status', post.id);
    confirmData.data = { is_active: status };
}
const styledTag = (value) => {
    return value.replace(
        /<a /g,
        '<a class="text-blue-500 underline hover:text-red-500 hover:no-underline" '
    );
}


</script>

<template>
    <Head title="Post Moderation's" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>

            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Post Moderation's | Public Posts
            </li>
        </Breadcrumbs>

        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                        <!-- Header -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                            <!-- Input -->
                            <div class="sm:col-span-1">
                                <label for="hs-as-table-product-review-search" class="sr-only">Search</label>
                                <div class="relative">
                                    <input type="text"
                                           class="py-2 px-3 ps-11 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600"
                                           placeholder="Search"
                                           v-model="search"
                                    >
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-4">
                                        <Search class="shrink-0 size-4 text-gray-400" />
                                    </div>
                                </div>
                            </div>
                            <!-- End Input -->
                        </div>
                        <!-- End Header -->

                        <!-- Table -->
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                            No.
                                        </span>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                            User
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start w-96">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Posted Item
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-center">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Posted Media
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Date
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-start">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                          Published
                                        </span>
                                    </div>
                                </th>

                                <th scope="col" class="px-6 py-3 text-center">
                                    <div class="flex items-center gap-x-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-800 dark:text-neutral-200">
                                            Action
                                        </span>
                                    </div>
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            <tr v-for="(post, index) in posts.data" key="user.id" class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                <td class="size-px whitespace-nowrap align-top">
                                    <a class="block p-6" href="#">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">{{ index+1 }}</span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap align-top">
                                    <a class="block p-6" href="#">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">{{ post.author.name }}</span>
                                    </a>
                                </td>
                                <td class="size-px whitespace-nowrap p-6 align-top">
                                    <span class="text-sm text-gray-600 dark:text-neutral-400 text-wrap" v-html="styledTag(post.post)"></span>
                                </td>
                                <td class="size-px whitespace-nowrap text-center">
                                    <div v-if="post.media && post.media.length > 0" class="flex -space-x-2">
                                        <template v-for="(media, index) in post.media.filter(m => m.mime_type.startsWith('image/') || m.mime_type === 'application/pdf').slice(0, 3)" :key="media.id">
                                            <img v-if="media.mime_type === 'application/pdf'"
                                                 src="../../../images/pdf-icon.svg"
                                                 :alt="media.name"
                                                 class="inline-block size-[46px]"
                                            />
                                            <img v-else
                                                 :src="media.preview_url"
                                                 :alt="media.name"
                                                 class="inline-block size-[46px]"
                                            />
                                        </template>
                                        <div v-if="post.media.filter(m => m.mime_type.startsWith('image/') || m.mime_type === 'application/pdf').length > 3" class="hs-dropdown [--placement:top-left] relative inline-flex">
                                            <button class="inline-flex items-center justify-center size-[46px] rounded-full bg-gray-100 border-2 border-white font-medium text-gray-700 shadow-sm align-middle hover:bg-gray-200 focus:outline-none focus:bg-gray-300 text-sm dark:bg-neutral-700 dark:text-white dark:hover:bg-neutral-600 dark:focus:bg-neutral-600 dark:border-neutral-800">
                                                <span class="font-medium leading-none">+{{ post.media.filter(m => m.mime_type.startsWith('image/') || m.mime_type === 'application/pdf').length - 3 }}</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 p-6">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">No Media</span>
                                    </div>
                                </td>
                                <td class="size-px whitespace-nowrap align-top">
                                    <a class="block p-6" href="#">
                                        <span class="text-sm text-gray-600 dark:text-neutral-400">{{ post.created_at }}</span>
                                    </a>
                                </td>

                                <td class="h-px w-72 min-w-72 text-start align-top">
                                    <a class="block p-6" href="#">
                                        <span v-if="post.published === 1" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                          <CheckCircle class="size-3" /> Approved
                                        </span>
                                        <span v-else-if="post.published === 0" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                                          <MinusCircle class="size-3" /> Not Published
                                        </span>
                                    </a>
                                </td>

                                <td class="size-px whitespace-nowrap align-top text-center">
                                    <div class="p-6">
                                        <div class="hs-dropdown [--placement:bottom-right] relative inline-block">
                                            <button id="hs-table-dropdown-1" type="button" class="hs-dropdown-toggle py-1.5 px-2 inline-flex justify-center items-center gap-2 rounded-lg text-gray-700 align-middle disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-blue-600 transition-all text-sm dark:text-neutral-400 dark:hover:text-white dark:focus:ring-offset-gray-800" aria-haspopup="menu" aria-expanded="false" aria-label="Dropdown">
                                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                                            </button>
                                            <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden divide-y divide-gray-200 min-w-40 z-20 bg-white shadow-2xl rounded-lg p-2 mt-2 dark:divide-neutral-700 dark:bg-neutral-800 dark:border dark:border-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-table-dropdown-1">
                                                <div class="py-2 first:pt-0 last:pb-0">
                                                    <a @click="changeStatus(post, 1)" class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700 dark:focus:text-neutral-300" href="#" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal" data-hs-overlay="#confirm-dialog">
                                                        Approve
                                                    </a>
                                                    <a @click="changeStatus(post, 0)" class="flex items-center gap-x-3 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-300 dark:focus:bg-neutral-700 dark:focus:text-neutral-300" href="#" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal" data-hs-overlay="#confirm-dialog">
                                                        Reject
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <!-- End Table -->

                        <!-- Footer -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <Pagination :links="posts.links" />
                            <p class="text-sm text-gray-500 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                Showing {{ posts.from }} to {{ posts.to }} of {{ posts.total }} results
                            </p>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->

        <ConfirmDialog :data="confirmData"/>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
