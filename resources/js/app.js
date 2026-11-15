import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Frans HRIS';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.config.globalProperties.$can = function (menuKey, action = 'view') {
            const auth = this.$page?.props?.auth;
            if (!auth || !auth.user) return false;
            if (auth.is_owner || auth.user.role === 'owner' || auth.user.role === 'admin') {
                return true;
            }
            return Boolean(auth.permissions?.[menuKey]?.[action]);
        };

        app.use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#10b981',
        showSpinner: false,
    },
});
