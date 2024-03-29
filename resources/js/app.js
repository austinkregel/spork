import './bootstrap';
import '../css/app.css';
import Toaster from "@meforma/vue-toaster";
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { createStore } from "vuex";
import Hero from './Builder/Components/Heros/Classic.vue';
import BuilderButton from './Builder/Components/BuilderButton.vue';
import BuilderText from './Builder/Components/BuilderText.vue';
import Notifications from 'notiwind';
import Grid from './Components/Grid.vue';
import TitleAndFooterTextCard from "./Builder/Components/Cards/TitleAndFooterTextCard.vue";
const registeredComponents = [];
const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

window.Spork = {

};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const store = createStore({
            modules: {

            },
            getters: {
                appName(getters, rootGetters) {
                    return 'Spork App';
                },
                components(getters, rootGetters) {
                    return registeredComponents;
                }
            }
        })

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Notifications)
            .use(store)
            .use(Toaster)
            .use(ZiggyVue, Ziggy);
        const addComponentToRegistry = (component) => {
            const name = (component.__name ?? component.name ?? component.__file ?? '').split('/').pop().split('.')[0];
            registeredComponents.push({
                id: (component.__id ?? component.id ?? component.__file ?? '').split('/').pop().split('.')[0],
                name,
                svg: component?.icon ?? `<svg data-darkreader-inline-stroke="" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"></path>
</svg>`,
                props: Object.keys(component.props).reduce((obj, key) => {
                    const defaultValue = component.props[key]?.default ?? null;

                    if (typeof defaultValue === 'function') {
                        return {
                            ...obj,
                            [key]: defaultValue()
                        }
                    }

                    return {
                        ...obj,
                        [key]: defaultValue
                }
                }, {}),
                children: component.children ?? [],
            })
            app.component(name, component);

            return app;
        }
        Spork.store = store;
        Spork.addComponentToRegistry = addComponentToRegistry;
        Spork.app = app;

        addComponentToRegistry(Hero);
        addComponentToRegistry(BuilderButton);
        addComponentToRegistry(BuilderText);
        addComponentToRegistry(Grid);
        addComponentToRegistry(TitleAndFooterTextCard);
        if (props?.initialPage?.props?.auth?.user?.id) {
            Echo.private(`App.Models.User.${props?.initialPage?.props?.auth?.user?.id}`)
                .listen('.MessageCreated', (e) => {
                    router.reload({
                        only: ['messages', 'unread_email_count'],
                    });
                })
                .listen('.AccountUpdated', e => {
                    router.refresh({
                        only: ['accounts', 'unread_email_count']
                    })
                });
        }

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
