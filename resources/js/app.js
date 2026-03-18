import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from 'ziggy-js';
import Layout from '@/Layouts/AppLayout.vue';
import ar from '@/locales/ar.json';
import en from '@/locales/en.json';

const i18n = createI18n({
    locale: 'ar',
    fallbackLocale: 'en',
    messages: { ar, en },
    legacy: false,
});

createInertiaApp({
    title: (title) => title ? `${title} — PM-OS` : 'PM-OS',

    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        // Auto-assign layout unless page defines its own
        page.default.layout = page.default.layout || Layout;

        return page;
    },

    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(i18n)
            .use(ZiggyVue)
            .mount(el);
    },

    progress: {
        color: '#2563eb',
        showSpinner: true,
    },
});
