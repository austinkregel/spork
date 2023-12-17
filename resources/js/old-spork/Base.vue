<template>
    <div class="w-full min-h-full flex bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-stone-200 relative">
        <!-- Tailwind open button -->
        <div class="absolute top-0 z-20 mt-14 left-0" :class="!$store.getters.hidingRootNav ? 'ml-12 2xl:ml-[15.25rem]' : '-ml-2'">
            <button @click="$store.dispatch('toggleRootNav')"
            class="rounded-full bg-white dark:bg-stone-900 border-2 border-stone-50 dark:border-stone-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-opacity-50">
                <ChevronRightIcon v-if="$store.getters.hidingRootNav" class="text-stone-200 dark:text-stone-400 h-6 w-6" />
                <ChevronLeftIcon v-else class="text-stone-200 dark:text-stone-400 h-6 w-6" />
            </button>
        </div>

        <div class="w-14 2xl:w-64 flex flex-col justify-between" :class="$store.getters.hidingRootNav ? '-ml-14 2xl:-ml-64' : ''">
            <div class="flex flex-col gap-2 text-stone-700 dark:text-stone-200">
                <div class="p-4 flex items-center gap-4 text-2xl">
                    <svg class="h-10" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 583 583"><circle cx="291.5" cy="291.5" r="291.5" fill="#15bc9c"/><circle cx="340.75" cy="284.46" r="30.16" fill="#8adece"/><path fill="#fff" d="M230.18 427.2V115.59h-48.24V471.43h221.13V427.2H230.18z"/></svg>
                    <span class="2xl:block hidden">{{ $store.getters.appName }}</span>
                </div>

                <nav class="flex-1 px-2 -my-2" aria-label="Sidebar">
                    <template v-for="(group, $i) in $store.getters.navigation" :key="group">
                        <div class="text-sm uppercase font-bold tracking-widest text-stone-800 dark:text-stone-400 2xl:px-2 py-1 my-2">
                            {{$i}}
                        </div>
                        <template v-for="item in group" :key="item.name">
                            <div>
                                <div v-if="!item.children">
                                    <router-link :to="item.href" :class="[item.current ? 'bg-stone-100 dark:bg-stone-900 text-stone-100' : 'bg-stone-200 dark:bg-stone-800 text-stone-200 hover:bg-stone-700 hover:text-stone-200', 'group w-full flex items-center pl-2 py-2 text-sm font-medium rounded-md']">
                                        <component :is="item.icon" :class="[item.current ? 'text-stone-100' : 'text-stone-200 group-hover:text-stone-300', 'mr-4 flex-shrink-0 h-6 w-6 stroke-current']" aria-hidden="true" />
                                        {{ item.name }}
                                    </router-link>
                                </div>
                                <Disclosure as="div" v-else class="space-y-1" v-slot="{ open }">
                                    <DisclosureButton :class="[item.current ? 'bg-stone-100 dark:bg-stone-900 text-stone-100' : 'bg-stone-100 dark:bg-stone-800 text-stone-200 hover:bg-stone-700 hover:text-stone-50', 'group w-full flex items-center pl-2 pr-1 py-2 text-left text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-slate-500']">
                                        <component :is="item.icon" class="mr-4 flex-shrink-0 h-6 w-6 text-stone-200 group-hover:text-stone-50 stroke-current" aria-hidden="true" />
                                        <span class="flex-1 2xl:block hidden">
                                            {{ item.name }}
                                        </span>
                                        <svg :class="[open ? 'rotate-90' : '', 'text-stone-100 ml-4 flex-shrink-0 h-5 w-5 transform group-hover:text-stone-50 transition-colors ease-in-out duration-150']" viewBox="0 0 20 20" aria-hidden="true">
                                            <path d="M6 6L14 10L6 14V6Z" fill="currentColor" />
                                        </svg>
                                    </DisclosureButton>
                                    <DisclosurePanel class="space-y-1">
                                        <router-link v-for="subItem in item.children" :key="subItem.name" :to="subItem.href" class="group w-full flex items-center pr-2 py-2 text-sm font-medium rounded-md hover:text-stone-100 hover:bg-stone-700">
                                            <component :is="subItem.icon" class="mr-4 flex-shrink-0 h-6 w-6 text-stone-200 group-hover:text-stone-50 ml-2 xl:hidden block stroke-current" aria-hidden="true" />
                                            <span class="xl:block hidden xl:pl-12">{{ subItem.name }}</span>
                                        </router-link>
                                    </DisclosurePanel>
                                </Disclosure>
                            </div>
                        </template>
                    </template>
                </nav>
            </div>
            <div class="flex-grow flex items-end px-4">
                Jobs {{ totalJobs }}
            </div>
            <div class="flex w-full flex flex-col-reverse 2xl:flex-row lg:gap-4 md:justify-between items-center p-4 text-stone-100">

                <div class="flex flex-wrap gap-2 items-center" v-if="$store.getters.isAuthenticated">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-white dark:bg-stone-500">
                        <img :src="$store.getters.isAuthenticated.profile_photo" alt="User photo"/>
                    </div>
                    <div class="flex-col hidden 2xl:flex">
                        <div class="text-sm font-medium">{{ $store.getters.isAuthenticated.name}}</div>
                        <div class="text-sm font-thin">{{ $store.getters.isAuthenticated.email}}</div>
                    </div>
                </div>
                <div class="flex flex-col items-center">
                    <Popover class="relative h-8 mt-2">
                        <PopoverButton>
                            <BellIcon class="w-6 h-6 text-stone-800 dark:text-stone-300 fill-current"></BellIcon>
                            <div v-if="$store.getters.notifications.length > 0" class="absolute top-0 right-0 w-4 h-4 p-1 -mt-2 -mr-2 rounded bg-red-500 flex items-center justify-center">{{ $store.getters.notifications.length}}</div>
                        </PopoverButton>

                        <PopoverPanel class="absolute z-10 rounded shadow bg-white dark:bg-stone-600 p-2 bottom-0 mb-8 left-0" style="width: 20rem; height:30rem;">
                            <div class="flex justify-between border-b border-stone-500 dark:border-stone-700 pb-1 mb-1">
                                <span class="uppercase font-medium">Notifications</span>
                                <router-link to="/settings">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </router-link>
                            </div>
                            <div class="grid grid-cols-1">
                                <div class="flex flex-col gap-2" v-if="$store.getters.notifications.length > 0">
                                    <DisplayNotification v-for="notification in $store.getters.notifications" :notification="notification" :key="notification.id" />
                                </div>
                                <div v-else>
                                    <div class="text-center italic text-stone-500 dark:text-stone-200">You're all caught up!</div>
                                </div>
                            </div>
                        </PopoverPanel>
                    </Popover>
                </div>
            </div>
        </div>
        <div class="flex-1 bg-stone-50 dark:bg-stone-900 min-h-screen overflow-y-scroll scrollbar scrollbar-thin scrollbar-thumb-stone-800 scrollbar-track-stone-500">
            <router-view></router-view>
        </div>

        <audio id="glitch-sound" src="/glitch-in-the-matrix-600.ogg" preload="auto" type="audio/ogg" />
        <audio id="finished-sound" src="/just-saying-593.ogg" preload="auto" type="audio/ogg" />
        <audio id="error-sound" src="/relentless-572.ogg" preload="auto" type="audio/ogg" />
        <audio id="notification-sound" src="/swiftly-610.ogg" preload="auto" type="audio/ogg" />
        <audio id="success-sound" src="/i-did-it-message-tone.ogg" preload="auto" type="audio/ogg" />
        <audio id="achievement-sound" src="/achievement-message-tone.ogg" preload="auto" type="audio/ogg" />
        <knowledge ></knowledge>
    </div>
</template>
<script>
import { ref } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel, Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import {
    CubeIcon,
    RefreshIcon,
    BellIcon,
    ChevronRightIcon,
    ChevronLeftIcon,
    AnnotationIcon,
} from '@heroicons/vue/outline'
import DisplayNotification from '@/components/DisplayNotification';
import collect from 'collect.js'
export default {
    components: {
        CubeIcon,
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        RefreshIcon,
        Popover, PopoverButton, PopoverPanel,
        BellIcon,
        ChevronLeftIcon,
        ChevronRightIcon,
        DisplayNotification,
        AnnotationIcon,
    },
    setup() {
        return {
            open: ref(false),
            icons: ref([]),
        }
    },
    computed: {
        totalJobs() {
            return Object.keys(this.$store.getters.totalJobs).length
        }
    },
    watch: {
        // We're watching this here to make sure the audio matches what is shown to the user.
        '$store.getters.notifications'(newValue, oldValue) {
            if (newValue.length === 0) {
                Spork.sound('achievement');
                return;
            }

            if (newValue.length > oldValue.length) {
                Spork.sound('notification');
                return;
            }
        },
    },
    mounted() {

    }
}
</script>
