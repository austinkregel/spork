<script setup>
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from "@/Layouts/AppLayout.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassEmptyState from "@/Components/Glass/GlassEmptyState.vue";
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';

const page = usePage();

const variable = (params) => Object.keys(params)[0];
const types = (params) => params[Object.keys(params)[0]];

const addListenerForEvent = async ({ event }) => {
    await axios.post('/api/logic/add-listener-for-event', { event, listener: 'App\\Listeners\\DebugEventListener' });
    setTimeout(() => router.reload({ only: ['events'] }), 1000);
};

const removeListenerForEvent = async ({ event }, listener) => {
    await axios.post('/api/logic/remove-listener-for-event', { event, listener });
    setTimeout(() => router.reload({ only: ['events'] }), 1000);
};
</script>

<template>
    <AppLayout title="Logic">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard
                title="Ways to handle events in your app"
                subtitle="Logic"
            />

            <div v-if="page.props.events?.length" class="grid gap-4 lg:grid-cols-2">
                <GlassSurface v-for="event in page.props.events" :key="event.event" class="flex flex-col gap-3 p-4">
                    <div class="font-mono text-sm font-bold tracking-wider text-stone-900 dark:text-stone-50">{{ event.event }}</div>

                    <div v-if="event.constructor.length > 0" class="space-y-1">
                        <div class="text-xs font-semibold uppercase tracking-widest text-stone-500 dark:text-stone-400">Constructor</div>
                        <div v-for="(key, idx) in event.constructor" :key="idx" class="flex items-center gap-2 text-xs">
                            <div class="w-1/3 text-stone-500 dark:text-stone-400">
                                <div v-for="(type, ti) in types(key)" :key="ti">{{ type }}</div>
                            </div>
                            <div class="w-1/2 font-mono font-bold text-indigo-500 dark:text-indigo-300">${{ variable(key) }}</div>
                        </div>
                    </div>
                    <div v-else class="rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-2 py-1 text-xs italic text-stone-500 dark:text-stone-400">
                        Nothing is in the constructor
                    </div>

                    <div v-if="Object.keys(event.methods ?? {}).length" class="space-y-1">
                        <div v-for="(methodText, method) in event.methods" :key="method" class="rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-2">
                            <pre class="font-mono text-xs text-amber-500 dark:text-amber-400">{{ method }}</pre>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="overflow-hidden rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <div
                                v-for="listener in event.listeners"
                                :key="listener"
                                class="flex items-center justify-between bg-stone-100/40 dark:bg-stone-800/40 px-2 py-1 font-mono text-xs"
                            >
                                <span class="text-indigo-500 dark:text-indigo-300">{{ listener }}<span class="text-stone-500">::</span><span class="text-amber-500">class</span></span>
                                <button
                                    type="button"
                                    aria-label="Remove listener"
                                    class="rounded-md border border-stone-300 dark:border-stone-700 px-1.5 text-stone-700 dark:text-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                    @click="removeListenerForEvent(event, listener)"
                                >
                                    −
                                </button>
                            </div>
                        </div>

                        <div v-if="event.listeners.length === 0" class="rounded-md bg-stone-100/40 dark:bg-stone-800/40 px-2 py-1 text-xs italic text-stone-500 dark:text-stone-400">
                            No listeners are defined for this event
                        </div>

                        <div class="flex justify-end">
                            <Menu as="div" class="relative inline-block text-left">
                                <MenuButton class="rounded-md border border-stone-300 dark:border-stone-700 px-2 py-1 text-xs text-stone-700 dark:text-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                    + Listener
                                </MenuButton>
                                <transition
                                    enter-active-class="transition motion-safe:ease-out motion-safe:duration-100 motion-reduce:duration-0"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition motion-safe:ease-in motion-safe:duration-75 motion-reduce:duration-0"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <MenuItems class="absolute right-0 z-10 mt-2 w-72 origin-top-right rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass shadow-lg focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-for="listener in page.props.listeners" :key="listener" v-slot="{ active }">
                                                <button
                                                    type="button"
                                                    :class="[active ? 'bg-stone-100/40 dark:bg-stone-800/40' : '', 'block w-full px-4 py-2 text-left text-xs']"
                                                    @click="addListenerForEvent(event, listener)"
                                                >
                                                    <span class="text-indigo-500 dark:text-indigo-300">{{ listener.replace('App\\', '') }}<span class="text-stone-500">::</span><span class="text-amber-500">class</span></span>
                                                </button>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </GlassSurface>
            </div>

            <GlassEmptyState
                v-else
                icon="BoltIcon"
                title="No events defined"
                description="Define an event in your application code to wire up listeners here."
            />
        </div>
    </AppLayout>
</template>
