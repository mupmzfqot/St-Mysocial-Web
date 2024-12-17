<script setup>
import {Head, Link} from "@inertiajs/vue3";
import HomeLayout from "@/Layouts/HomeLayout.vue";
import { MessageSquareMore } from "lucide-vue-next";
import PostContent from "@/Components/PostContent.vue";

defineProps({
    user: Object,
    totalPosts: Number,
    totalComments: Number,
    totalLikes: Number,
    posts: Object
})
</script>

<template>
    <Head title="Profile" />
    <HomeLayout>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <!-- Background -->
            <div class="h-40 bg-cover bg-center" :style="{ backgroundImage: `url(${user.cover_image})` }">
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
                    <Link :href="route('message.show', user.id)" type="button" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        <MessageSquareMore class="shrink-0 size-4" /> Send Message
                    </Link>
                </div>
            </div>
        </div>

        <div class="py-4">
            <h2 class="font-medium mb-2">Recent Posts</h2>
            <PostContent :posts="posts" />
        </div>
    </HomeLayout>
</template>

<style scoped>

</style>
