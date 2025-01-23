<script setup>
import Post from "@/Components/Post.vue";
import {Head, Link} from "@inertiajs/vue3";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {ChevronRight} from "lucide-vue-next";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Comment from "@/Components/Comment.vue";

defineProps({
    post: {
        type: Object,
    }
})
</script>

<template>
    <Head title="Post Detail" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>

            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Post Detail
            </li>
        </Breadcrumbs>

        <!-- Card -->
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 w-2/3 inline-block align-middle">
                    <div  class="flex flex-col text-wrap bg-white border shadow-sm rounded-xl py-3 px-4 mb-2 dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
                        <Post :content="post" :status="false"/>

                        <hr class="-mx-4 mt-2" />
                        <div class="flex-grow overflow-y-auto custom-scrollbar py-2">
                            <!-- Comments Section -->

                            <div class="mt-1">
                                <p class="text-md font-semibold mb-3 text-gray-800 dark:text-white">
                                    Comments
                                </p>
                                <Comment
                                    :post-id="post.id"
                                    :comments="post?.comments || []"
                                    :current-user="$page.props.auth.user"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>

<style scoped>

</style>
