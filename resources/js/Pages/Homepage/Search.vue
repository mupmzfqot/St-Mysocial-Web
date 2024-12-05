<script setup>
import {Head, Link, router} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {CheckCircle, ChevronRight, MinusCircle, Search, UserCircle} from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ref, watch} from "vue";
import {debounce} from "lodash";
import Pagination from "@/Components/Pagination.vue";
import HomeLayout from "@/Layouts/HomeLayout.vue";

const props = defineProps({
    users: Object,
    searchTerm: String
});

const search = ref(props.searchTerm);

watch(
    search, debounce(
        (q) => router.get(route('user.search'), { search: q }, { preserveState: true }), 500
    )
);
</script>

<template>
    <Head title="Posts" />
    <HomeLayout>
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

                        </div>
                        <!-- End Header -->

                        <!-- Search results -->
                        <div class=" bg-white shadow-sm rounded-lg">
                            <div class="bg-gray-100  py-3 px-4 md:py-4 md:px-5 dark:bg-neutral-900 dark:border-neutral-700">
                                <p class="mt-1 font-light text-sm">
                                    Search results:
                                </p>
                            </div>
                            <div class="p-1 gap-y-3">
                                <Link :href="route('profile.show', user.id)" v-for="user in users.data" class="shrink-0 group block p-2 hover:bg-gray-100 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="hs-tooltip inline-block">
                                            <a class="hs-tooltip-toggle relative inline-block" href="#">
                                                <img class="inline-block size-[40px] rounded-full" :src="user.avatar" alt="Avatar">
                                                <!--                                            <span class="absolute bottom-0 end-0 block size-3 rounded-full ring-2 ring-white bg-green-700"></span>-->
                                            </a>
                                        </div>
                                        <div class="ms-3">
                                            <h3 class="font-semibold text-sm text-gray-800 dark:text-white">{{ user.name }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-neutral-500">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </Link>
                            </div>


                        </div>
                        <!-- End search results -->

                        <!-- Footer -->
                        <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                            <Pagination :links="users.links" />
                            <p class="text-sm text-gray-500 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                            </p>
                        </div>
                        <!-- End Footer -->
                    </div>
                </div>
            </div>
        </div>
        <!-- End Card -->
    </HomeLayout>
</template>

<style scoped>

</style>
