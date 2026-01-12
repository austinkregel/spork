<script setup>
import { computed, ref, watch, useSlots } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const slots = useSlots();
const page = usePage();

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: null,
    },
    navItems: {
        type: Array,
        default: () => [],
    },
    sidebarWidth: {
        type: String,
        default: 'w-72 xl:w-90',
    },
    showSearch: {
        type: Boolean,
        default: false,
    },
    searchValue: {
        type: String,
        default: '',
    },
    searchPlaceholder: {
        type: String,
        default: 'Search',
    },
    contentWidthClass: {
        type: String,
        default: 'max-w-5xl',
    },
    contentPaddingClass: {
        type: String,
        default: 'px-4 lg:px-6 py-4',
    },
});

const emit = defineEmits(['update:searchValue', 'search']);

const internalSearch = ref(props.searchValue ?? '');

watch(
    () => props.searchValue,
    (value) => {
        if (value === internalSearch.value) {
            return;
        }

        internalSearch.value = value ?? '';
    }
);

const updateSearch = (value) => {
    emit('update:searchValue', value);
    emit('search', value);
};

watch(internalSearch, (value) => updateSearch(value));

const resolvedNavItems = computed(() =>
    props.navItems.map((item) => {
        if (Object.prototype.hasOwnProperty.call(item, 'active')) {
            return item;
        }

        const href = item.href ?? '#';
        const active = page.url.startsWith(href);

        return {
            ...item,
            active,
        };
    })
);

const hasSidebarHeader = computed(() => Boolean(props.title || props.subtitle || props.showSearch || slots['sidebar-header']));
const hasSidebarFooter = computed(() => Boolean(slots['sidebar-footer']));
</script>

<template>
    <AppLayout :title="title">
        <div class="flex h-[calc(100vh-65px)] divide-x divide-stone-200 dark:divide-stone-800 bg-white dark:bg-stone-900">
            <aside :class="[sidebarWidth, 'shrink-0 flex flex-col bg-stone-50 dark:bg-stone-950']">
                <div v-if="hasSidebarHeader" class="border-b border-stone-200 dark:border-stone-800 p-4 space-y-3">
                    <slot name="sidebar-header">
                        <p v-if="subtitle" class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            {{ subtitle }}
                        </p>
                        <h1 v-if="title" class="text-2xl font-semibold text-stone-800 dark:text-white">
                            {{ title }}
                        </h1>
                        <div v-if="showSearch" class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-4 w-4 text-stone-400" />
                            <input
                                v-model="internalSearch"
                                type="search"
                                class="w-full rounded-md border border-stone-200 dark:border-stone-800 bg-white/80 dark:bg-stone-900 pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-stone-800 dark:text-stone-100 placeholder-stone-400"
                                :placeholder="searchPlaceholder"
                            />
                        </div>
                    </slot>
                </div>

                <div class="flex-1 overflow-y-auto custom-scroll">
                    <slot name="sidebar">
                        <nav v-if="resolvedNavItems.length > 0" class="divide-y divide-stone-200 dark:divide-stone-800">
                            <Link
                                v-for="item in resolvedNavItems"
                                :key="item.href ?? item.name"
                                :href="item.href ?? '#'"
                                class="flex items-center justify-between px-4 py-3 text-sm focus:outline-none"
                                :class="item.active
                                    ? 'bg-stone-100 dark:bg-stone-900 text-stone-900 dark:text-stone-100'
                                    : 'text-stone-500 dark:text-stone-300 hover:text-stone-900 hover:bg-stone-50 dark:hover:bg-stone-900/60'"
                                :aria-current="item.active ? 'page' : undefined"
                            >
                                <span class="flex items-center gap-3">
                                    <DynamicIcon
                                        v-if="item.icon"
                                        :icon-name="item.icon"
                                        class="w-5 h-5"
                                        :class="item.active ? 'text-indigo-500 dark:text-indigo-300' : 'text-stone-400 dark:text-stone-500'"
                                        :active="Boolean(item.active)"
                                    />
                                    <span class="font-medium">{{ item.label ?? item.name }}</span>
                                </span>
                                <span
                                    v-if="item.badge !== undefined"
                                    class="text-xs font-semibold px-2 py-0.5 rounded-full border border-stone-200 dark:border-stone-700 text-stone-500 dark:text-stone-300"
                                >
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </nav>

                        <div v-else class="p-4 text-sm text-stone-500 dark:text-stone-400">
                            <slot name="sidebar-empty">No sections available.</slot>
                        </div>
                    </slot>
                </div>

                <div v-if="hasSidebarFooter" class="border-t border-stone-200 dark:border-stone-800 p-4 text-xs text-stone-500 dark:text-stone-400">
                    <slot name="sidebar-footer" />
                </div>
            </aside>

            <section class="flex-1 min-w-0 flex flex-col relative bg-stone-100/70 dark:bg-stone-900 overflow-x-hidden">
                <div v-if="$slots.header" class="border-b border-stone-200 dark:border-stone-800 px-6 py-4 bg-white/80 dark:bg-stone-900/80 backdrop-blur">
                    <slot name="header" />
                </div>

                <main :class="['flex-1 overflow-y-auto overflow-x-hidden custom-scroll', contentPaddingClass]">
                    <div :class="['w-full', contentWidthClass, 'mx-auto']">
                        <slot />
                    </div>
                </main>

                <div v-if="$slots.footer" class="border-t border-stone-200 dark:border-stone-800 px-6 py-4 bg-white dark:bg-stone-900">
                    <slot name="footer" />
                </div>
            </section>
        </div>
    </AppLayout>
</template>

