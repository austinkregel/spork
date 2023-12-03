<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                Settings
            </h2>
        </template>
        <div class="">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex gap-5">
                 <aside class="flex overflow-x-auto border-b border-zinc-900/5 dark:border-zinc-50/5 lg:block lg:w-64 lg:flex-none lg:border-0 my-12">
                    <nav class="flex-none px-4 sm:px-6 lg:px-0">
                        <ul role="list" class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col">
                            <li v-for="item in secondaryNavigation" :key="item.name">
                                <a :href="item.href" :class="[item.current ? 'bg-zinc-50 dark:bg-zinc-800 text-indigo-600 dark:text-indigo-300' : 'text-zinc-700 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-zinc-50 dark:hover:bg-zinc-900', 'group flex gap-x-3 rounded-md py-2 pl-2 pr-3 text-sm leading-6 font-semibold']">
                                    <component :is="item.icon" :class="[item.current ? 'text-indigo-600' : 'text-zinc-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']" aria-hidden="true" />
                                    {{ item.name }}
                                </a>
                            </li>
                        </ul>
                    </nav>
                </aside>

                <main class="px-4 sm:px-6 lg:flex-auto lg:px-0 py-12">
                    <slot/>
                </main>
            </div>
        </div>
    </AppLayout>
</template>

<script>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CrudView from "@/Components/Spork/CrudView.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import {buildUrl} from "@kbco/query-builder";
import { SwitchLabel, SwitchGroup, Switch } from '@headlessui/vue';
import { Bars3Icon } from '@heroicons/vue/20/solid'
import {
    BellIcon,
    CreditCardIcon,
    CubeIcon,
    FingerPrintIcon,
    UserCircleIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

export default {
    components: {
        CrudView,
        AppLayout,
        SporkInput,
        SwitchLabel, SwitchGroup, Switch,
        Bars3Icon,
        BellIcon,
        CreditCardIcon,
        CubeIcon,
        FingerPrintIcon,
        UserCircleIcon,
        UsersIcon,
        XMarkIcon,
    },
    setup() {

        const navigation = [


            { name: 'Home', href: '#' },
            { name: 'Invoices', href: '#' },
            { name: 'Clients', href: '#' },
            { name: 'Expenses', href: '#' },
        ]
        const secondaryNavigation = [
            { name: 'General', href: '#', icon: UserCircleIcon, current: true },
            { name: 'Security', href: '#', icon: FingerPrintIcon, current: false },
            { name: 'Notifications', href: '#', icon: BellIcon, current: false },
            { name: 'Plan', href: '#', icon: CubeIcon, current: false },
            { name: 'Billing', href: '#', icon: CreditCardIcon, current: false },
            { name: 'Team members', href: '#', icon: UsersIcon, current: false },
        ]

        return {
            createOpen: ref(false),
            form: ref(({
                name: '',
                settings: {},
            })),
            secondaryNavigation,
            navigation,
            data: ref([]),
            pagination: ref({}),

        }
    },

}
</script>

<style scoped>

</style>
