<script setup>

import {Head, Link, router, useForm} from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import {CheckCircle, ChevronRight, MinusCircle} from "lucide-vue-next";
import TextInput from "@/Components/TextInput.vue";
import {onMounted, reactive, ref} from "vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import StrongPassword from "@/Components/StrongPassword.vue";
import TogglePassword from "@/Components/TogglePassword.vue";
import {HSStaticMethods} from "preline";

const props = defineProps({
    user: Object,
    allPosts: Number,
    totalPost: Number,
    totalLikes: Number,
    totalComments: Number,
})

const form = useForm({
    name: props.user.name,
    username: props.user.username,
    email: props.user.email,
});

const updateProfile = () => {
    form.post(route('user.update', props.user.id));
}

let specials = '!@#$%^&*()_+{}:"<>?\|[];\',./`~';
let lowercase = 'abcdefghijklmnopqrstuvwxyz';
let uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
let numbers = '0123456789';

let all = specials + lowercase + uppercase + numbers;

String.prototype.pick = function(min, max) {
    var n, chars = '';

    if (typeof max === 'undefined') {
        n = min;
    } else {
        n = min + Math.floor(Math.random() * (max - min));
    }

    for (var i = 0; i < n; i++) {
        chars += this.charAt(Math.floor(Math.random() * this.length));
    }

    return chars;
};

String.prototype.shuffle = function() {
    let array = this.split('');
    let tmp, current, top = array.length;

    if (top) while (--top) {
        current = Math.floor(Math.random() * (top + 1));
        tmp = array[current];
        array[current] = array[top];
        array[top] = tmp;
    }

    return array.join('');
};

const randomPassword = (specials.pick(2) + lowercase.pick(3) + uppercase.pick(2) + numbers.pick(3)).shuffle();

const confirmData = reactive({
    confirmId: ''
})

const updateStatus = () => {
    let status = props.user.is_active ? 'Block' : 'Activate';
    confirmData.id = props.user.id;
    confirmData.message = `Do you want to ${status} user account?`;
    confirmData.url = route('admin.update-status', props.user.id)
    confirmData.data = { is_active : props.user.is_active !== 1 };
}

const passwordResetForm = useForm({
    password: '',
    password_confirmation: ''
});

const isPasswordResetModalOpen = ref(false);

const openPasswordResetModal = () => {
    isPasswordResetModalOpen.value = true;
};

const closePasswordResetModal = () => {
    isPasswordResetModalOpen.value = false;
    passwordResetForm.reset();
};

const submitPasswordReset = () => {
    passwordResetForm.post(route('user.reset-password', props.user.id), {
        onSuccess: () => {
            closePasswordResetModal();
        }
    });
};

const verifyAccount = () => {
    let status = props.user.is_active ? 'Unverified' : 'Verified';
    confirmData.id = props.user.id;
    confirmData.message = `Do you want to ${status} user account?`;
    confirmData.url = route('user.verify', props.user.id)
    confirmData.data = { verified_account : props.user.verified_account !== 1 };
}

onMounted(() => {
    HSStaticMethods.autoInit();
});


</script>

<template>
    <Head title="Account Info" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <Link :href="route('dashboard')" class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </Link>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                Account Info
            </li>
        </Breadcrumbs>

        <div class="grid lg:grid-cols-4 gap-4">
            <div class="lg:col-span-3">
                <!-- Card -->
                <div class="flex flex-col mb-4">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                                <!-- Header -->
                                <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                                            Account Info
                                        </h2>
                                    </div>
                                </div>
                                <!-- End Header -->

                                <!-- Table -->
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                    <thead class="bg-gray-50 dark:bg-neutral-800">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-500">
                                            Name
                                        </th>

                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-500">
                                            Value/Count
                                        </th>

                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-500">
                                            Action
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">Full Name</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">{{ user.name }}</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                        </td>
                                    </tr>

                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">Username</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">{{ user.username }}</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                        </td>
                                    </tr>

                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">Email</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">{{ user.email }}</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                        </td>
                                    </tr>

                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">SignUp Date</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">{{ user.created_at }}</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                        </td>
                                    </tr>

                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">Account State</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <a class="block" href="#">
                                                    <span v-if="user.is_active" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">
                                                        <CheckCircle class="size-3" />Active
                                                    </span>
                                                    <span v-else-if="!user.is_active" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">
                                                        <MinusCircle class="size-3" />Blocked
                                                    </span>
                                                </a>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <a href="#" v-if="user.is_active" @click="updateStatus" class="text-sm text-red-400 dark:text-red-800"  aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal" data-hs-overlay="#confirm-dialog">
                                                Block Account
                                            </a>
                                            <a href="#" v-if="!user.is_active" @click="updateStatus" class="text-sm text-green-600 dark:text-green-800"  aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal" data-hs-overlay="#confirm-dialog">
                                                Activate Account
                                            </a>
                                        </td>
                                    </tr>

<!--                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">-->
<!--                                        <td class="size-px whitespace-nowrap">-->
<!--                                            <div class="px-6 py-2">-->
<!--                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">Account Verified</p>-->
<!--                                            </div>-->
<!--                                        </td>-->
<!--                                        <td class="h-px w-72 min-w-72">-->
<!--                                            <div class="px-6 py-2">-->
<!--                                                <span v-if="user.verified_account" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-teal-100 text-teal-800 rounded-full dark:bg-teal-500/10 dark:text-teal-500">-->
<!--                                                    <CheckCircle class="size-3" />Verified-->
<!--                                                </span>-->
<!--                                                <span v-else-if="!user.verified_account" class="py-1 px-3 inline-flex items-center gap-x-1 text-xs font-medium bg-red-100 text-red-800 rounded-full dark:bg-red-500/10 dark:text-red-500">-->
<!--                                                    <MinusCircle class="size-3" />Not Verified-->
<!--                                                </span>-->
<!--                                            </div>-->
<!--                                        </td>-->
<!--                                        <td class="h-px w-72 min-w-72">-->
<!--                                            <a href="#" v-if="user.verified_account" @click="verifyAccount" class="text-sm text-red-400 dark:text-red-800"  aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal" data-hs-overlay="#confirm-dialog">-->
<!--                                                Set as Unverified Account-->
<!--                                            </a>-->
<!--                                            <a href="#" v-if="!user.verified_account" @click="verifyAccount" class="text-sm text-green-600 dark:text-green-800"  aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-scale-animation-modal" data-hs-overlay="#confirm-dialog">-->
<!--                                                Set as Verified Account-->
<!--                                            </a>-->
<!--                                        </td>-->
<!--                                    </tr>-->

                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">Total User Posts (Published)</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">{{ totalPost }}</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                        </td>
                                    </tr>

                                    <tr class="bg-white hover:bg-gray-50 dark:bg-neutral-900 dark:hover:bg-neutral-800">
                                        <td class="size-px whitespace-nowrap">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">User Posts</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                            <div class="px-6 py-2">
                                                <p class="block text-sm text-gray-800 dark:text-neutral-200">{{ allPosts }}</p>
                                            </div>
                                        </td>
                                        <td class="h-px w-72 min-w-72">
                                        </td>
                                    </tr>

                                    </tbody>
                                </table>
                                <!-- End Table -->
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Card -->

                <!-- Card -->
                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                                <!-- Header -->
                                <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                                            Edit Profile
                                        </h2>
                                    </div>
                                </div>
                                <!-- End Header -->

                                <form @submit.prevent="updateProfile">
                                    <!-- Table -->
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                        <tr>

                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <span class="text-sm text-gray-600 dark:text-neutral-400">Username</span>
                                                </div>
                                            </td>
                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <TextInput
                                                        id="username"
                                                        type="text"
                                                        class="mt-1 block w-full"
                                                        v-model="form.username"
                                                        required
                                                        autofocus
                                                        autocomplete="username"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>

                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <span class="text-sm text-gray-600 dark:text-neutral-400">Full Name</span>
                                                </div>
                                            </td>
                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <TextInput
                                                        id="name"
                                                        type="text"
                                                        class="mt-1 block w-full"
                                                        v-model="form.name"
                                                        required
                                                        autofocus
                                                        autocomplete="name"
                                                    />
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <span class="text-sm text-gray-600 dark:text-neutral-400">Email</span>
                                                </div>
                                            </td>
                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <TextInput
                                                        id="email"
                                                        type="text"
                                                        class="mt-1 block w-full"
                                                        v-model="form.email"
                                                        required
                                                        autofocus
                                                        autocomplete="email"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <!-- End Table -->

                                    <!-- Footer -->
                                    <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                                        <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                                            Save changes
                                        </button>

                                    </div>
                                    <!-- End Footer -->
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Card -->

                <!-- Card -->
                <div class="flex flex-col mt-2">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden dark:bg-neutral-900 dark:border-neutral-700">
                                <!-- Header -->
                                <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200 dark:border-neutral-700">
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                                            Reset Password
                                        </h2>
                                    </div>
                                </div>
                                <!-- End Header -->

                                <form @submit.prevent="submitPasswordReset">
                                    <!-- Table -->
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                        <tr>

                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <span class="text-sm text-gray-600 dark:text-neutral-400">Password</span>
                                                </div>
                                            </td>
                                            <td class="size-px whitespace-nowrap w-3/4">
                                                <div class="px-6 py-3">
                                                    <StrongPassword v-model="passwordResetForm.password" />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>

                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <span class="text-sm text-gray-600 dark:text-neutral-400">Confirm Password</span>
                                                </div>
                                            </td>
                                            <td class="size-px whitespace-nowrap">
                                                <div class="px-6 py-3">
                                                    <TogglePassword v-model="passwordResetForm.password_confirmation" />
                                                </div>
                                            </td>
                                        </tr>

                                        <tr  v-if="passwordResetForm.errors.password">
                                            <td class="col-span-2">
                                                <div class="px-6 py-3">
                                                    <p class="text-sm text-red-600 mt-2">{{ passwordResetForm.errors.password }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <!-- End Table -->

                                    <!-- Footer -->
                                    <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200 dark:border-neutral-700">
                                        <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                                            Reset Password
                                        </button>

                                    </div>
                                    <!-- End Footer -->
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Card -->
            </div>
            <div class="">
                <div class="min-h-60 flex flex-col bg-white border shadow-sm rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:shadow-neutral-700/70">
                    <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                        <img class="inline-block size-[120px] rounded-full"
                             :src="user.avatar"
                             alt="Avatar"
                        >
                        <h3 class="text-lg mt-3 font-bold text-gray-800 dark:text-white">
                            {{ user.name }}
                        </h3>
                        <p class="text-center text-gray-500 dark:text-neutral-400">
                            {{ user.email }}
                        </p>
                        <div class="flex items-center gap-x-20 mt-5">
                            <div class="text-center">
                                <p class="font-semibold text-gray-800 dark:text-neutral-400">{{ totalPost }}</p>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">Posts</p>
                            </div>
                            <div class="text-center">
                                <p class="font-semibold text-gray-800 dark:text-neutral-400">{{ totalLikes }}</p>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">Likes</p>
                            </div>
                            <div class="text-center">
                                <p class="font-semibold text-gray-800 dark:text-neutral-400">{{ totalComments }}</p>
                                <p class="text-sm text-gray-600 dark:text-neutral-400">Comments</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="isPasswordResetModalOpen" @close="closePasswordResetModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Reset Password
            </h2>

            <form @submit.prevent="submitPasswordReset" class="mt-6 space-y-6">
                <div>
                    <label for="password" class="block text-sm mb-2 dark:text-white">Password</label>
                    <div class="relative">
                        <StrongPassword required v-model="passwordResetForm.password" />
                    </div>
                    <p class="text-sm text-red-600 mt-2" v-if="passwordResetForm.errors.password">{{ passwordResetForm.errors.password }}</p>
                </div>

                <div>
                    <label for="confirm-password" class="block text-sm mb-2 dark:text-white">Confirm Password</label>
                    <TogglePassword v-model="passwordResetForm.password_confirmation" placeholder="Confirm Password" />
                </div>

                <div class="mt-6 flex justify-end">
                    <PrimaryButton
                        :class="{ 'opacity-25': passwordResetForm.processing }"
                        :disabled="passwordResetForm.processing"
                    >
                        Reset Password
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

        <ConfirmDialog :data="confirmData" />
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
