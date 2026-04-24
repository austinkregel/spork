<script setup>
import { computed, useSlots } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';

const slots = useSlots();
const page = usePage();

const props = defineProps({
    title: { type: String, default: '' },
    subTitle: { type: String, default: '' },
    home: { type: String, default: '' },
    description: { type: Object, default: () => ({}) },
    contentWidthClass: { type: String, default: 'max-w-5xl' },
});

const availablePages = computed(() => page.props.subnavigation ?? []);
const navigationItems = computed(() =>
    availablePages.value.map((navItem) => ({
        ...navItem,
        label: navItem.label ?? navItem.name,
        active: navItem.active ?? page.url.startsWith(navItem.href ?? '#'),
    })),
);
</script>

<template>
    <AppLayout :title="title">
        <div class="mx-auto flex w-full max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <aside class="w-72 shrink-0 space-y-4 lg:sticky lg:top-24 lg:self-start">
                <GlassSurface class="p-4">
                    <slot name="sidebar-header">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p v-if="subTitle" class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                                    {{ subTitle }}
                                </p>
                                <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-50">{{ title }}</h1>
                            </div>
                            <Link
                                v-if="home"
                                :href="home"
                                class="inline-flex items-center rounded-md bg-stone-900 px-2.5 py-1 text-xs font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:bg-stone-100 dark:text-stone-900 dark:focus-visible:ring-offset-stone-950"
                            >
                                Home
                            </Link>
                        </div>
                    </slot>
                </GlassSurface>

                <GlassSurface v-if="navigationItems.length" class="overflow-hidden">
                    <nav aria-label="Manage sections" class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                        <Link
                            v-for="item in navigationItems"
                            :key="item.href ?? item.name"
                            :href="item.href ?? '#'"
                            :aria-current="item.active ? 'page' : undefined"
                            class="flex items-center justify-between px-4 py-3 text-sm transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset"
                            :class="item.active
                                ? 'bg-stone-100/60 text-stone-900 dark:bg-stone-700/40 dark:text-stone-50'
                                : 'text-stone-600 hover:bg-stone-100/40 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-800/40 dark:hover:text-stone-50'"
                        >
                            <span class="flex items-center gap-3">
                                <DynamicIcon
                                    v-if="item.icon"
                                    :icon-name="item.icon"
                                    class="h-5 w-5"
                                    :class="item.active ? 'text-indigo-500 dark:text-indigo-300' : 'text-stone-400 dark:text-stone-500'"
                                    :active="Boolean(item.active)"
                                />
                                <span class="font-medium">{{ item.label }}</span>
                            </span>
                            <GlassPill v-if="item.badge !== undefined" size="sm">{{ item.badge }}</GlassPill>
                        </Link>
                    </nav>
                </GlassSurface>

                <slot name="sidebar" />
            </aside>

            <section class="min-w-0 flex-1 space-y-6">
                <div v-if="slots.header" class="flex items-center justify-between">
                    <slot name="header" />
                </div>
                <div v-else class="flex items-center justify-between">
                    <div>
                        <p v-if="subTitle" class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">
                            {{ subTitle }}
                        </p>
                        <h2 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">{{ title }}</h2>
                    </div>
                </div>

                <slot />
            </section>
        </div>
    </AppLayout>
</template>
