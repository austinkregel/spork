<script setup>
import { computed, useSlots } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SplitNavigationShell from '@/Layouts/SplitNavigationShell.vue';

const slots = useSlots();
const page = usePage();

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subTitle: {
        type: String,
        default: '',
    },
    home: {
        type: String,
        default: '',
    },
    description: {
        type: Object,
        default: () => ({}),
    },
    contentWidthClass: {
        type: String,
        default: 'max-w-2xl',
    },
});

const availablePages = computed(() => page.props.subnavigation ?? []);
const navigationItems = computed(() =>
    availablePages.value.map((navItem) => ({
        ...navItem,
        label: navItem.name,
        active: navItem.active ?? page.url.startsWith(navItem.href),
    }))
);
</script>

<template>
    <SplitNavigationShell :title="title" :subtitle="subTitle" :nav-items="navigationItems" :sidebar-width="'w-72'">
        <template #sidebar-header>
            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p v-if="subTitle" class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">
                            {{ subTitle }}
                        </p>
                        <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">
                            {{ title }}
                        </h1>
                    </div>
                    <Link
                        v-if="home"
                        :href="home"
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-stone-900 text-white dark:bg-stone-100 dark:text-stone-900"
                    >
                        Home
                    </Link>
                </div>
            </div>
        </template>

        <template v-if="slots.header" #header>
            <slot name="header" />
        </template>
        <template v-else #header>
            <div class="flex items-center justify-between">
                <div>
                    <p v-if="subTitle" class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        {{ subTitle }}
                    </p>
                    <h2 class="text-2xl font-semibold text-stone-900 dark:text-white">
                        {{ title }}
                    </h2>
                </div>
            </div>
        </template>

        <slot />
    </SplitNavigationShell>
</template>
