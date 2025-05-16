import './bootstrap';
import '../css/app.css';
import 'preline';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import FlashMessage from "@/Components/FlashMessage.vue";
import { VueReCaptcha } from 'vue-recaptcha-v3';

const appName = import.meta.env.VITE_APP_NAME || 'TeamST';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueReCaptcha, {
                siteKey: import.meta.env.VITE_RECAPTCHA_SITE_KEY,
                loaderOptions: {
                    autoHideBadge: false,
                    badge: 'bottomright', // 'bottomright', 'bottomleft', 'inline'
                },
            })
            .component('FlashMessage', FlashMessage)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

