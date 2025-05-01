<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ChevronRight } from "lucide-vue-next";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    config: Object,
});

const form = useForm({
    id: props.config?.id || null,
    host: props.config?.host || '',
    port: props.config?.port || 587,
    username: props.config?.username || '',
    password: props.config?.password || '',
    encryption: props.config?.encryption || '',
    sender: props.config?.sender || '',
    email: props.config?.email || '',
});

const submit = () => {
    let url = props.config?.id ? route('setting.smtp', {id: form.id}) : route('setting.smtp');
    form.post(url, {
        onSuccess: (page) => {
            const flash = page.props.flash;
            if (flash.success) {
                successMessage.value = flash.success;
                showSuccessMessage.value = true;
            }
        },
        onError: (error) => {
            errorMessage.value = Object.values(error)[0];
            showErrorMessage.value = true;
        },
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="App Setting" />
    <AuthenticatedLayout>
        <Breadcrumbs>
            <li class="inline-flex items-center">
                <a class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-none focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500" href="#">
                    Home
                </a>
                <ChevronRight class="shrink-0 mx-2 size-4 text-gray-400 dark:text-neutral-600" />
            </li>
            <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate" aria-current="page">
                App Setting
            </li>
        </Breadcrumbs>

        <div class="max-w-screen-lg">
            <div class="mx-auto p-4 bg-white border border-gray-300 rounded-lg shadow-sm">
                <div class="mb-2 border-b pb-2">
                    <label class="font-semibold text-gray-800 dark:text-neutral-200">SMTP Email Configuration</label>
                </div>

                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label for="smtp_host" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SMTP Host</label>
                        <TextInput id="smtp_host" v-model="form.host" type="text" class="mt-1 block w-full" required placeholder="smtp.example.com"/>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 mb-3">
                        <div>
                            <label for="smtp_sender" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sender Name</label>
                            <TextInput id="smtp_sender" v-model="form.sender" type="text" class="mt-1 block w-full" required placeholder="sender"/>
                        </div>
                        <div>
                            <label for="smtp_mail" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sender Email</label>
                            <TextInput id="smtp_mail" v-model="form.email" type="email" class="mt-1 block w-full" required placeholder="email"/>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 mb-3">
                        <div>
                            <label for="smtp_port" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SMTP Port</label>
                            <TextInput id="smtp_port" v-model="form.port" type="text" class="mt-1 block w-full" required placeholder="587"/>
                        </div>
                        <div>
                            <label for="smtp_encryption" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SMTP Encryption</label>
                            <select id="smtp_encryption" v-model="form.encryption" class="mt-1 block w-full border-gray-200 rounded-lg text-sm">
                                <option value="tls" selected>TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 mb-3">
                        <div>
                            <label for="smtp_username" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SMTP Username</label>
                            <TextInput id="smtp_username" v-model="form.username" type="text" class="mt-1 block w-full" required placeholder="username"/>
                        </div>
                        <div>
                            <label for="smtp_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SMTP Password</label>
                            <TextInput id="smtp_password" v-model="form.password" type="password" class="mt-1 block w-full" required placeholder=""/>
                        </div>
                    </div>
                    <button type="submit" class="py-3 mt-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border hover:bg-gray-700 focus:outline-none text-white bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
