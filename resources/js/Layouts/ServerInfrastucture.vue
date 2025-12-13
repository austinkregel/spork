<template>
    <AppLayout :title="title">
        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="lg:flex lg:items-start lg:gap-6">
                <aside class="w-full lg:w-80 xl:w-96 lg:sticky lg:top-24 lg:self-start space-y-6 mb-8 lg:mb-0">
                    <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-4 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                                    Server
                                </p>
                                <h2 class="text-2xl font-semibold text-stone-900 dark:text-white">
                                    {{ server.name }}
                                </h2>
                                <p class="text-xs text-stone-500 dark:text-stone-400">
                                    {{ server.ip_address ?? 'IP unknown' }}
                                </p>
                            </div>
                            <Status :status="server.status" />
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div v-for="item in summaryItems" :key="item.label">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                    {{ item.label }}
                                </p>
                                <p class="text-stone-800 dark:text-stone-200 font-medium">
                                    {{ item.value }}
                                </p>
                            </div>
                        </div>

                        <div v-if="(server.tags ?? []).length" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in server.tags"
                                :key="tag.id ?? tag.name"
                                class="px-2 py-1 text-xs rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300"
                            >
                                {{ tag.name ?? tag }}
                            </span>
                        </div>
                    </div>

                    <nav class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 shadow-sm divide-y divide-stone-200 dark:divide-stone-800">
                        <Link
                            v-for="item in navItems"
                            :key="item.id"
                            :href="item.href"
                            class="flex items-center gap-3 px-4 py-3 text-sm font-semibold transition"
                            :class="item.href === currentPath
                                ? 'text-indigo-600 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-900/30 border-l-2 border-indigo-600'
                                : 'text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-800'"
                        >
                            <DynamicIcon v-if="item.icon" :icon-name="item.icon" class="w-5 h-5" />
                            {{ item.name }}
                        </Link>
                    </nav>
                </aside>

                <main class="flex-1">
                    <slot />
                </main>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from '@inertiajs/vue3';
import DynamicIcon from "@/Components/DynamicIcon.vue";
import Status from "@/Components/Spork/Atoms/Status.vue";
import { computed, ref } from "vue";
import dayjs from 'dayjs';
import { buildServerNavigation } from '@/Pages/Infrastructure/serverNavigation';

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
    title: {
        type: String,
        default: 'Server',
    },
    navigation: {
        type: Array,
        default: () => [],
    },
});

const currentPath = ref(window.location.pathname);
const navItems = computed(() => (props.navigation.length ? props.navigation : buildServerNavigation(props.server)));

const formatTimestamp = (value) => (value ? dayjs(value).format('MMM D, YYYY h:mm A') : 'Never');

const summaryItems = computed(() => [
    { label: 'Provider', value: props.server.provider_label ?? props.server.provider ?? props.server.credential?.provider ?? 'Unspecified' },
    { label: 'vCPU', value: props.server.vcpu ?? '—' },
    { label: 'Memory', value: props.server.memory ? `${props.server.memory} GB` : '—' },
    { label: 'Storage', value: props.server.disk ? `${props.server.disk} GB` : '—' },
    { label: 'Last ping', value: formatTimestamp(props.server.last_ping_at) },
    { label: 'Cost/hr', value: props.server.cost_per_hour ? `$${Number(props.server.cost_per_hour).toFixed(2)}` : '—' },
]);
</script>
