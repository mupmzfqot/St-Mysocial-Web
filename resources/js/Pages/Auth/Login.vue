<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import TogglePassword from "@/Components/TogglePassword.vue";
import {ref, computed, onUnmounted} from 'vue';
import { useReCaptcha, VueReCaptcha } from 'vue-recaptcha-v3';
import { getCurrentInstance } from 'vue'

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    }
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    captcha: '',
});

const instance = getCurrentInstance()
const app = instance.appContext.app
if (!app._installedRecaptcha) {
    app.use(VueReCaptcha, { siteKey: import.meta.env.VITE_RECAPTCHA_SITE_KEY,  loaderOptions: {
        autoHideBadge: false,
        badge: 'bottomright', // 'bottomright', 'bottomleft', 'inline'
    }});
    app._recaptchaInitialized = true
}

const { executeRecaptcha } = useReCaptcha()

const loginError = ref({
    message: '',
    remainAttempts: null,
    maxAttempts: null,
    unlockAt: null,
    captcha: null,
});

const submit = async() => {
    loginError.value = {
        message: '',
        remainAttempts: null,
        maxAttempts: null,
        unlockAt: null
    };

    form.captcha = await executeRecaptcha('login')

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            if (errors.email && Array.isArray(errors.email)) {
                const errorDetails = errors.email[0];
                loginError.value = {
                    message: errorDetails.message || 'Login failed',
                    remainAttempts: errorDetails.remain_attempts ?? null,
                    maxAttempts: errorDetails.max_attempts ?? null,
                    unlockAt: errorDetails.unlock_at ? new Date(errorDetails.unlock_at) : null
                };
            }
            form.reset('captcha');
        }
    });
};

const formattedUnlockTime = computed(() => {
    if (loginError.value.unlockAt) {
        return loginError.value.unlockAt.toLocaleString();
    }
    return null;
});

onUnmounted(() => {
    const badge = document.querySelector('.grecaptcha-badge')
    if (badge && badge.parentNode) {
        badge.parentNode.removeChild(badge)
    }
})
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <!-- Rate Limit Error Message -->
        <div v-if="loginError.message" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <p>{{ loginError.message }}</p>

            <!-- Remaining Attempts Message -->
            <p v-if="loginError.remainAttempts !== null && loginError.remainAttempts >= 0">
                Remaining attempts: {{ loginError.remainAttempts }} / {{ loginError.maxAttempts }}
            </p>

            <!-- Unlock Time Message -->
            <p v-if="formattedUnlockTime">
                You can try again after: {{ formattedUnlockTime }}
            </p>
        </div>

        <div class="flex flex-row bg-white border-gray-200 shadow-2xs rounded-xl lg:min-h-[24rem] lg:w-[38rem] sm:min-h-[23vh] sm:w-[550px] dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400">
            <div class="shrink-0 relative lg:w-[26rem] sm:w-[250px] rounded-t-xl overflow-hidden pt-[40%] sm:rounded-s-xl sm:max-w-[250px] md:rounded-se-none md:max-w-xs">
                <img class="size-full absolute top-0 start-0 object-cover" src="../../../images/background.webp" alt="Card Image">
            </div>
            <div class="flex flex-1 justify-center lg:px-6 lg:py-4 sm:p-3 flex flex-col w-full">
                <h3 class="lg:text-xl sm:text-lg text-center font-bold text-gray-800 dark:text-white">Login</h3>
                <div :class="[form.errors.email ? 'mt-0': 'mt-3']">
                    <div class="py-2" v-if="form.errors.email">
                        <div class="bg-yellow-50 border border-red-400 text-sm text-red-800 rounded-lg p-4 dark:bg-yellow-800/10 dark:border-yellow-900 dark:text-yellow-500" role="alert" tabindex="-1" aria-labelledby="hs-with-description-label">
                            <div class="flex">
                                <div class="shrink-0">
                                    <svg class="shrink-0 size-4 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                        <path d="M12 9v4"></path>
                                        <path d="M12 17h.01"></path>
                                    </svg>
                                </div>
                                <div class="ms-4">
                                    <h3 id="hs-with-description-label" class="text-sm font-semibold">
                                        Login Failed!
                                    </h3>
                                    <div class="mt-1 text-xs text-gray-800">
                                        {{ form.errors.email }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel :class="'text-xs'" for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-0.5 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                        </div>

                        <div class="lg:mt-3 sm:mt-2">
                            <InputLabel :class="'text-xs'" for="password" value="Password" />
                            <TogglePassword :class="'mt-0.5 block w-full'" v-model="form.password" />
                        </div>
<!-- 
                        <div class="block lg:mt-4 sm:mt-3">
                            <img :src="captchaSrc" alt="" class="lg:h-12 sm:h-10 rounded-md" />
                        </div>

                        <div class="block lg:mt-3 sm:mt-2 lg:w-[10rem] sm:w-[160px]">
                            <TextInput
                                id="captcha"
                                type="text"
                                class="mt-1 block text-xs"
                                v-model="form.captcha"
                                placeholder="Enter Captcha"
                                required
                                autofocus
                            />
                            <div class="text-red-600 text-xs mt-0.5" v-if="form.errors.captcha">
                                {{ form.errors.captcha }}
                            </div>
                        </div> -->

                        <div class="block lg:mt-3 sm:mt-2">
                            <label class="flex items-center">
                                <Checkbox name="remember" v-model:checked="form.remember" />
                                <span class="ms-2 text-xs text-gray-600 dark:text-gray-400">Remember me</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between lg:mt-4 sm:mt-3 pb-2">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }, 'text-xs'" :disabled="form.processing">
                                Log in
                            </PrimaryButton>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="underline text-xs text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                            >
                                Forgot your password?
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
