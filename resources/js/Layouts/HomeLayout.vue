<script setup>
import {onMounted, ref, watch} from 'vue';
import {Link, router, usePage} from "@inertiajs/vue3";
import {CircleCheckBig, Heart, Images, Video, LogOut, MessageSquareMore, Star, UserIcon, House} from "lucide-vue-next";
import {debounce} from "lodash";
import {useUnreadMessages} from '@/Composables/useUnreadMessages';
import {useWebSocket} from '@/Composables/useWebSocket';
import {TeamStore} from '@/Composables/useTeamStore';
import {ToastifyContainer} from "vue3-toastify";
import 'vue3-toastify/dist/index.css';
// import WebSocketAlert from '@/Components/WebSocketAlert.vue';

const { auth: { roles: userRoles } } = usePage().props;
const { unreadNotifications: notifications } = usePage().props;
const teams = ref([]);

const isPublic = userRoles.includes("public_user");
const isST = userRoles.includes("user");
const { unreadMessageCount, fetchUnreadMessageCount } = useUnreadMessages();
const { isConnected, subscribeToChannel, connectionStatus } = useWebSocket();

const readNotification = (item) => {
    router.visit(route('read-notification', item.id), {
        preserveScroll: true,
        method: 'post',
        onSuccess: visit => {
            router.get(item.data.url)
        }
    })
}

const fetchTeams = async () => {
    teams.value = await TeamStore.fetchTeams();
}

onMounted(async () => {
    window.HSStaticMethods.autoInit();
    fetchTeams();
    fetchUnreadMessageCount();

    // Subscribe to message notifications
    const messageCallback = (event) => {
            fetchUnreadMessageCount();
    };

    const errorCallback = (error) => {
        console.error('Message notification channel error:', error);
    };

    try {
        await subscribeToChannel('message.notification', 'NewMessage', messageCallback, errorCallback);
    } catch (error) {
        console.error('❌ Failed to subscribe to message notifications:', error);
    }
});

const props = defineProps({
    searchTerm: String
});

const search = ref(props.searchTerm);

watch(
    search, debounce(
        (q) => router.get(route('user.search'), { search: q }, { preserveState: true }), 500
    )
);

const currentPath = ref(usePage().url)

function isActiveNav(path) {
    return currentPath.value === path
}



</script>

<template>
    <!-- WebSocket Alert -->
    <!-- <WebSocketAlert 
        :show-alert="['connecting', 'reconnecting', 'failed'].includes(connectionStatus)" 
        :connection-status="connectionStatus" 
    /> -->
    
    <!-- ========== HEADER ========== -->
    <header class="sticky bg-primary-gradient-reverse top-0 inset-x-0 flex flex-wrap md:justify-start md:flex-nowrap z-[48] w-full bg-white border-b text-sm py-2.5  dark:bg-neutral-800 dark:border-neutral-700">
        <nav class="max-w-[85rem] mx-auto w-full px-4 sm:px-6 lg:px-8 flex basis-full items-center">
            <div class="me-5">
                <!-- Logo -->
                <a class="flex-none rounded-md text-xl inline-block font-semibold focus:outline-none focus:opacity-80" href="#" aria-label="">
                    <img src="../../images/logo.png" alt="Logo" class="h-10" />
                </a>
                <!-- End Logo -->
            </div>

            <div class="w-full flex items-center justify-end ms-auto md:justify-between gap-x-1 md:gap-x-3">
                <!-- Collapse -->
                <div class="md:hidden">
                    <button type="button" class="hs-collapse-toggle size-[38px] relative inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700" id="hs-secondaru-navbar-collapse" aria-expanded="false" aria-controls="hs-secondaru-navbar" aria-label="Toggle navigation" data-hs-collapse="#hs-secondaru-navbar">
                        <svg class="hs-collapse-open:hidden size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
                        <svg class="hs-collapse-open:block shrink-0 hidden size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        <span class="sr-only">Toggle navigation</span>
                    </button>
                </div>
                <!-- End Collapse -->

                <div class="hidden md:block"></div>

                <div class="flex flex-row items-center justify-end gap-2">
                    <div class="hidden md:block">
                        <!-- Search Input -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none z-20 ps-3.5">
                                <svg class="shrink-0 size-4 text-gray-400 dark:text-white/60" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </div>
                            <input type="text" v-model="search" class="py-2 ps-10 pe-16 block w-full bg-white border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder:text-neutral-400 dark:focus:ring-neutral-600" placeholder="Search">
                            <div class="hidden absolute inset-y-0 end-0 flex items-center pointer-events-none z-20 pe-1">
                                <button type="button" class="inline-flex shrink-0 justify-center items-center size-6 rounded-full text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-600" aria-label="Close">
                                    <span class="sr-only">Close</span>
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                                </button>
                            </div>

                        </div>
                        <!-- End Search Input -->
                    </div>

                    <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
                        <button type="button" id="hs-dropdown-with-dividers" class="hs-dropdown-toggle relative inline-flex justify-center items-center size-[38px] text-sm font-semibold rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
                            </svg>
                            <span class="absolute top-0 end-0 inline-flex items-center py-0.5 px-1.5 rounded-full text-xs font-medium transform -translate-y-1/2 translate-x-1/2 bg-red-500 text-white">{{ notifications.length > 99 ? '99+' : notifications.length }}</span>
                        </button>

                        <div class="hs-dropdown-menu transition-[opacity,margin] w-[350px] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-60 bg-white shadow-md border border-gray-200 rounded-lg mt-2 divide-y divide-gray-200 dark:bg-neutral-800 dark:border dark:border-neutral-700 dark:divide-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-with-dividers">
                            <div class="p-1 space-y-0.5">
                                <p class="flex items-center gap-x-3.5 py-2 px-3 font-semibold text-gray-800">
                                    Notifications
                                </p>
                            </div>
                            <div class="p-1 space-y-0.5">
                                <a href="#" @click="readNotification(notif)" v-for="notif in notifications.slice(0, 9)" class="flex items-center gap-x-1 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                                    {{ notif.data.message }}
                                </a>
                                <a href="#" v-if="notifications.length === 0" class="flex items-center gap-x-3.5 py-2 px-3 font-light italic text-gray-800">
                                    No notifications.
                                </a>
                            </div>
                            <div class="p-1 space-y-0.5">
                                <Link :href="route('notifications')" class="flex items-center gap-x-1 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                                    <CircleCheckBig class="shrink-0 size-4 text-green-700" /> Show all notifications
                                </Link>
                            </div>

                        </div>
                    </div>

                    <!-- Dropdown -->
                    <div class="hs-dropdown [--placement:bottom-right] relative inline-flex">
                        <button id="hs-dropdown-custom-trigger" type="button" class="hs-dropdown-toggle py-1 ps-1 pe-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-full border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                            <img class="w-8 h-auto rounded-full object-cover" :src="$page.props.auth.user.avatar" alt="Avatar">
                            <svg class="hs-dropdown-open:rotate-180 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>

                        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 border border-gray-200 hidden min-w-60 bg-white shadow-md rounded-lg mt-2 dark:bg-neutral-800 dark:border dark:border-neutral-700" role="menu" aria-orientation="vertical" aria-labelledby="hs-dropdown-custom-trigger">
                            <div class="py-3 px-5 bg-gray-100 rounded-t-lg dark:bg-neutral-700">
                                <p class="text-sm text-gray-500 dark:text-neutral-500">Signed in as</p>
                                <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $page.props.auth.user.name }}</p>
                            </div>
                            <div class="p-1 space-y-0.5">
                                <Link :href="route('profile.edit')" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                                    <UserIcon class="shrink-0 size-4" />Profile
                                </Link>
                                <Link :href="route('logout')" as="button" method="post" class="w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100">
                                    <LogOut class="shrink-0 size-4" />Log Out
                                </Link>
                            </div>
                        </div>
                    </div>
                    <!-- End Dropdown -->

                </div>
            </div>
        </nav>
        
        <!-- Mobile Navigation Menu -->
        <div id="hs-secondaru-navbar" class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow md:hidden bg-white border-b border-gray-200" aria-labelledby="hs-secondaru-navbar-collapse">
            <div class="overflow-hidden overflow-y-auto max-h-[75vh]">
                <div class="py-2 md:py-0 flex flex-col md:flex-row md:items-center md:justify-end gap-0.5 md:gap-1">
                    <!-- User Info -->
                    <div class="px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center">
                            <img class="w-10 h-10 rounded-full object-cover" :src="$page.props.auth.user.avatar" alt="Avatar">
                            <div class="ml-3">
                                <h3 class="font-semibold text-gray-800">{{ $page.props.auth.user.name }}</h3>
                                <p class="text-sm text-gray-500">{{ $page.props.auth.user.email }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Links -->
                    <Link v-if="isST" :href="route('homepage')" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <House class="w-4 h-4 mr-3 text-blue-600" />
                        Home
                    </Link>
                    <Link v-if="isST" :href="route('message.index')" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <MessageSquareMore class="w-4 h-4 mr-3 text-green-600" />
                        Messages
                        <span v-if="unreadMessageCount > 0" class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ unreadMessageCount }}</span>
                    </Link>
                    <Link :href="route('liked-posts')" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <Heart class="w-4 h-4 mr-3 text-red-600" />
                        My Likes
                    </Link>
                    <Link :href="route('top-posts')" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <Star class="w-4 h-4 mr-3 text-yellow-600" />
                        Top Posts
                    </Link>
                    <Link :href="route('photoAlbum.index')" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <Images class="w-4 h-4 mr-3 text-amber-600" />
                        My Photos
                    </Link>
                    <Link :href="route('videos.index')" class="flex items-center px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        <Video class="w-4 h-4 mr-3 text-pink-600" />
                        My Videos
                    </Link>
                </div>
            </div>
        </div>
    </header>
    <!-- ========== END HEADER ========== -->

    <!-- ========== MAIN CONTENT ========== -->
    <main id="content">
        <div class="w-full lg:max-w-[85rem] max-h-screen mx-auto py-6 px-4 sm:px-6 lg:px-0">
            <div class="flex flex-wrap gap-4">
                <div class="hidden lg:block w-[20%]">
                    <div class="max-w-xs flex flex-col bg-white shadow-sm rounded-lg">
                        <Link :href="route('profile.edit')" class="shrink-0 group block px-4 py-3">
                            <div class="flex items-center">
                                <div class="hs-tooltip inline-block">
                                    <a class="hs-tooltip-toggle relative inline-block" href="#">
                                        <img class="inline-block size-[45px] rounded-full object-cover" :src="$page.props.auth.user.avatar" alt="Avatar">
                                        <span class="absolute bottom-0 end-0 block size-3 rounded-full ring-2 ring-white bg-green-700"></span>
                                    </a>
                                </div>
                                <div class="ms-3">
                                    <h3 class="font-semibold text-gray-800 dark:text-white">{{ $page.props.auth.user.name }}</h3>
                                    <p class="text-sm font-medium text-gray-400 dark:text-neutral-500">{{ $page.props.auth.user.email }}</p>
                                </div>
                            </div>
                        </Link>
                        <Link v-if="isST" :href="route('homepage')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/home') ? 'text-blue-600' : '']">
                            <House class="shrink-0 size-5 text-blue-800" />
                            Home
                        </Link>
<!--                        <Link :href="route('public')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/public') ? 'text-blue-600' : '']">-->
<!--                            <Newspaper class="shrink-0 size-5 text-purple-800 fill-purple-200" />-->
<!--                            Public Posts-->
<!--                        </Link>-->
                        <Link v-if="isST" :href="route('message.index')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/message') ? 'text-blue-600' : '']">
                            <MessageSquareMore class="shrink-0 size-5 text-green-800 fill-green-100" />
                            Messages
                            <span v-if="unreadMessageCount > 0" class="inline-flex items-center py-0.5 px-1.5 rounded-full text-xs font-medium bg-red-500 text-white ms-auto">{{ unreadMessageCount }}</span>
                        </Link>
                        <Link :href="route('liked-posts')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/liked-posts') ? 'text-blue-600' : '']">
                            <Heart class="shrink-0 size-5 text-red-600 fill-red-600" />
                            My Likes
                        </Link>
                        <Link :href="route('top-posts')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/top-posts') ? 'text-blue-600' : '']">
                            <Star class="shrink-0 size-5 text-yellow-600 fill-yellow-500" />
                            Top Posts
                        </Link>
                        <Link :href="route('photoAlbum.index')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/photo-album') ? 'text-blue-600' : '']">
                            <Images class="shrink-0 size-5 text-amber-800" />
                            My Photos
                        </Link>
                        <Link :href="route('videos.index')" type="button" :class="['inline-flex hover:bg-blue-100 items-center gap-x-2 py-3 px-4 text-sm font-semibold text-start border border-gray-200 -mt-px first:rounded-t-lg first:mt-0 last:rounded-b-lg focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:border-neutral-700', isActiveNav('/videos') ? 'text-blue-600' : '']">
                            <Video class="shrink-0 size-5 text-pink-900 fill-pink-200" />
                            My Videos
                        </Link>
                    </div>
                </div>
                <div class="w-full lg:w-[55%]
                    h-[90vh] overflow-y-auto
                    [&::-webkit-scrollbar]:w-1
                    [&::-webkit-scrollbar-track]:rounded-full
                    [&::-webkit-scrollbar-track]:bg-gray-100
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    [&::-webkit-scrollbar-thumb]:bg-gray-300
                    dark:[&::-webkit-scrollbar-track]:bg-neutral-700
                    dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500"
                >
                    <slot />
                </div>
                <div class="hidden lg:block w-[20%]">
                    <div class="max-w-xs flex flex-col bg-white shadow-sm rounded-lg hs-accordion active" v-if="isST">
                        <div type="button" id="hs-basic" class="hs-accordion-toggle bg-gray-100 border-b hover:cursor-pointer rounded-t-xl py-3 px-3 dark:bg-neutral-900 dark:border-neutral-700"
                            aria-expanded="true" aria-controls="hs-basic">
                            <div class="flex flex-row justify-between items-center">
                                <h1 class="font-bold text-lg">
                                    <span class="text-gray-400">#</span>
                                    <span class="text-indigo-800">Team</span>
                                    <span class="text-red-700">ST</span>
                                </h1>
                                <svg class="hs-accordion-active:hidden block size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m6 9 6 6 6-6"></path>
                                </svg>
                                <svg class="hs-accordion-active:block hidden size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m18 15-6-6-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="hs-basic" aria-labelledby="hs-basic" class="hs-accordion-content
                            w-full overflow-hidden transition-[height] duration-300
                            p-1 gap-y-3 max-h-[70vh] overflow-y-auto
                            [&::-webkit-scrollbar]:w-1
                            [&::-webkit-scrollbar-track]:rounded-full
                            [&::-webkit-scrollbar-track]:bg-gray-100
                            [&::-webkit-scrollbar-thumb]:rounded-full
                            [&::-webkit-scrollbar-thumb]:bg-gray-300
                            dark:[&::-webkit-scrollbar-track]:bg-neutral-700
                            dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500">
                            <Link :href="route('profile.show', team.id)" v-for="team in teams" class="shrink-0 group block p-2 hover:bg-blue-100 rounded-lg">
                                <div class="flex items-center">
                                    <div class="hs-tooltip inline-block">
                                        <a class="hs-tooltip-toggle relative inline-block" href="#">
                                            <img class="inline-block size-[40px] object-cover rounded-full" :src="team.avatar" alt="Avatar">
<!--                                            <span class="absolute bottom-0 end-0 block size-3 rounded-full ring-2 ring-white bg-green-700"></span>-->
                                        </a>
                                    </div>
                                    <div class="ms-3">
                                        <h3 class="font-semibold text-sm text-gray-800 dark:text-white">{{ team.name }}</h3>
<!--                                        <p class="text-sm text-gray-600 dark:text-neutral-500">{{ team.email }}</p>-->
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
    <!-- ========== END MAIN CONTENT ========== -->

    <ToastifyContainer />
</template>

<style scoped>
.bg-gradient-blue {
    background: rgb(89,27,75);
    background: linear-gradient(90deg, rgba(89,27,75,1) 0%, rgba(46,137,228,1) 100%);
}
.bg-primary-gradient {
    background: rgb(68,30,167);
    background: -moz-linear-gradient(90deg, rgba(68,30,167,1) 0%, rgba(186,58,217,1) 100%);
    background: -webkit-linear-gradient(90deg, rgba(68,30,167,1) 0%, rgba(186,58,217,1) 100%);
    background: linear-gradient(90deg, rgba(68,30,167,1) 0%, rgba(186,58,217,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#441ea7",endColorstr="#ba3ad9",GradientType=1);
}
.bg-primary-gradient-reverse {
    background: rgb(186,58,217);
    background: -moz-linear-gradient(90deg, rgba(186,58,217,1) 0%, rgba(68,30,167,1) 100%);
    background: -webkit-linear-gradient(90deg, rgba(186,58,217,1) 0%, rgba(68,30,167,1) 100%);
    background: linear-gradient(90deg, rgba(186,58,217,1) 0%, rgba(68,30,167,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#ba3ad9",endColorstr="#441ea7",GradientType=1);
}
</style>
