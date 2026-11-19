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
            let perm = auth.permissions?.[menuKey]?.[action];
            if (perm === undefined && menuKey.startsWith('company_')) {
                const cleanKey = menuKey.replace(/^company_/, '');
                perm = auth.permissions?.[cleanKey]?.[action];
            } else if (perm === undefined) {
                perm = auth.permissions?.[`company_${menuKey}`]?.[action];
            }
            return Boolean(perm);
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
