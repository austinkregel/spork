<script setup>
import { ref, computed } from 'vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import GlobalChat from "@/Components/GlobalChat.vue";
import {
    Dialog,
    DialogPanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue'
import {
    Bars3Icon,
    BellIcon,
    CalendarIcon,
    ChartPieIcon,
    DocumentDuplicateIcon,
    FolderIcon,
    HomeIcon,
    UsersIcon,
    XMarkIcon,
    CpuChipIcon,
} from '@heroicons/vue/24/outline'
import { ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import DynamicIcon from "@/Components/DynamicIcon.vue";
const page = usePage()
defineProps({
    title: String,
    navigation: Array,
    subNavigation: Array,
});

const userNavigation = [
    { name: 'Your profile', href: '/user/profile' },
    { name: 'Query Builder', href: '/user/api-query' },
    { name: 'Sign out', href: '#' },
]

const notificationCount = computed(() => {
  return (page.props.unread_email_count ?? 0) + page.props.notifications.length
});
const sidebarOpen = ref(false)
const showingNavigationDropdown = ref(false);
const $settings = computed(() => page.props)
const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const user = computed(() => page.props.auth.user);
const currentNavigation = page.props?.current_navigation;
const subNavigation = computed(function () {
  const exactMatchPaths = page?.props?.navigation?.filter(i => i.href === location.pathname);

  if (exactMatchPaths.length > 0) {
    return exactMatchPaths[0].children;
  }

  const childNavItems = page?.props?.navigation?.filter(i => i.id === currentNavigation?.parent_id)

  if (childNavItems.length === 0) {
    return [];
  }

  return childNavItems[0].children;
});
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen">
        <Head :title="title" />
        <Banner />
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog as="div" class="relative z-0 lg:hidden" @close="sidebarOpen = false">
                <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-stone-900/80" />
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-transtone-x-full" enter-to="transtone-x-0" leave="transition ease-in-out duration-300 transform" leave-from="transtone-x-0" leave-to="-transtone-x-full">
                        <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                            <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                                <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                                    <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                        <span class="sr-only">Close sidebar</span>
                                        <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                                    </button>
                                </div>
                            </TransitionChild>
                            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-stone-900 px-6 pb-2 ring-1 ring-white/10">
                                <div class="flex h-16 shrink-0 items-center">
                                    <img class="h-8 w-auto" src="https://tailwindui.com/img/logos/mark.svg?color=slate&shade=500" alt="Your Company" />
                                </div>
                                <nav class="flex flex-1 flex-col">
                                    <ul role="list" class="-mx-2 flex-1 space-y-1">
                                        <li v-for="item in page.props.navigation" :key="item.name">
                                            <Link :href="item.href" :class="[item.current ? 'bg-stone-800 text-white' : 'text-stone-400 hover:text-white hover:bg-stone-800', 'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold']">
                                                <DynamicIcon :icon-name="item.icon" :active="item.current" class="h-6 w-6 shrink-0" aria-hidden="true" />
                                                {{ item.name }}
                                            </Link>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-10 lg:block lg:w-20 lg:bg-stone-950 lg:pb-4 relative">
            <Link href="/-/dashboard" class="flex h-16 shrink-0 items-center flex flex-col justify-center">
                <CpuChipIcon class="h-8 w-8 text-slate-500" />
            </Link>
            <div class="text-white absolute left-16 z-0 mr-8 -mt-4 bg-stone-700 rounded-full w-6 h-6">
                <ChevronDownIcon class="w-6 h-6 text-white -rotate-90" />
            </div>

            <nav class="mt-8">
                <div class="mt-6 w-full flex-1 space-y-1 px-2">
                    <Link v-for="item in page.props.navigation" :key="item.name" :href="item.href" :class="[item.current ? 'bg-slate-800 text-white' : 'text-slate-100 hover:bg-slate-800 hover:text-white', 'group flex w-full flex-col items-center rounded-md p-3 text-xs font-medium']" :aria-current="item.current ? 'page' : undefined">
                        <DynamicIcon :icon-name="item.icon"  :active="item.current"  :class="[item.current ? 'text-white' : 'text-slate-300 group-hover:text-white', 'h-6 w-6']" aria-hidden="true" />
                        <span class="mt-2">{{ item.name }}</span>
                    </Link>
                </div>
            </nav>
        </div>

        <div class="lg:pl-20 min-h-screen">
            <div class="sticky top-0 z-0 flex h-16 shrink-0 items-center gap-x-4 border-b border-stone-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" class="-m-2.5 p-2.5 text-stone-700 lg:hidden" @click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-stone-900/10 lg:hidden" aria-hidden="true" />

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <form class="relative flex flex-1" action="#" method="GET">
                        <label for="search-field" class="sr-only">Search</label>
                        <MagnifyingGlassIcon class="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-stone-400" aria-hidden="true" />
                        <input id="search-field" class="block h-full w-full border-0 dark:bg-neutral-900 py-0 pl-8 pr-0 text-stone-900 placeholder:text-stone-400 focus:ring-0 sm:text-sm" placeholder="Search..." type="search" name="search" />
                    </form>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <button @click="" class="-m-2.5 p-2.5 text-stone-400 hover:text-stone-500 relative">
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="h-6 w-6" aria-hidden="true" />
                            <span class="bg-red-500 absolute top-0 right-0 rounded-full text-sm text-white py-0.5 px-1">
                              {{ notificationCount }}
                            </span>
                        </button>

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-stone-900/10" aria-hidden="true" />

                        <!-- Profile dropdown -->
                        <Menu as="div" class="relative">
                            <MenuButton class="-m-1.5 flex items-center p-1.5">
                                <span class="sr-only">Open user menu</span>
                                <img class="h-8 w-8 rounded-full bg-stone-50" :src="user.profile_photo_url" alt="" />
                                <span class="hidden lg:flex lg:items-center">
                                  <span class="ml-4 text-sm font-semibold leading-6 text-stone-900 dark:text-neutral-50" aria-hidden="true">{{ user.name }}</span>
                                  <ChevronDownIcon class="ml-2 h-5 w-5 text-stone-400" aria-hidden="true" />
                                </span>
                            </MenuButton>
                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                <MenuItems class="absolute right-0 z-0 mt-2.5 w-32 origin-top-right rounded-md bg-white dark:bg-stone-800 py-2 shadow-lg ring-1 ring-stone-900/5 focus:outline-none">
                                    <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
                                        <Link :href="item.href" :class="[active ? 'bg-stone-50 dark:bg-stone-700' : '', 'block px-3 py-1 text-sm leading-6 dark:text-stone-50 text-stone-900']">{{ item.name }}</Link>
                                    </MenuItem>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>

            <main class=""  style="max-height: calc(100vh - 65px);">
                <slot/>
            </main>
        </div>

        <global-chat />
    </div>
</template>
