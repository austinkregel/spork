<template>
  <header
    class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] backdrop-blur-glass px-4 sm:px-6"
  >
    <button
      type="button"
      class="lg:hidden -m-2.5 rounded-md p-2.5 text-stone-700 transition-colors motion-reduce:transition-none hover:bg-stone-200/60 dark:text-stone-200 dark:hover:bg-stone-700/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
      @click="$emit('open-drawer')"
    >
      <span class="sr-only">Open navigation</span>
      <Bars3Icon class="h-6 w-6" aria-hidden="true" />
    </button>

    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
      <form class="flex flex-1 items-center" :action="route('search')" method="get">
        <label class="sr-only" for="spork-search">Search</label>
        <GlassInput
          id="spork-search"
          v-model="query"
          name="query"
          type="search"
          placeholder="Search…"
        />
      </form>
    </div>

    <Menu as="div" class="relative z-20">
      <MenuButton
        class="relative -m-1.5 inline-flex items-center rounded-md p-1.5 text-stone-700 transition-colors motion-reduce:transition-none hover:bg-stone-200/60 dark:text-stone-200 dark:hover:bg-stone-700/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
      >
        <span class="sr-only">View notifications</span>
        <BellIcon class="h-6 w-6" aria-hidden="true" />
        <span
          v-if="notificationCount > 0"
          class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-xs font-semibold text-white"
          :aria-label="`${notificationCount} unread notifications`"
        >
          {{ notificationCount }}
        </span>
      </MenuButton>
      <transition
        enter-active-class="motion-safe:transition motion-safe:ease-out motion-safe:duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="motion-safe:transition motion-safe:ease-in motion-safe:duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <MenuItems
          class="absolute right-0 z-50 mt-2.5 w-96 origin-top-right rounded-md border border-[var(--color-glass-border-light)] bg-[var(--color-glass-surface-strong-light)] py-2 shadow-lg backdrop-blur-glass focus:outline-none dark:border-[var(--color-glass-border-dark)] dark:bg-[var(--color-glass-surface-strong-dark)]"
        >
          <MenuItem v-for="notification in notifications" :key="notification.id" class="px-2">
            <NotificationBody :notification="notification" />
          </MenuItem>
          <div class="border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-3 py-2">
            <Link :href="route('notifications')" class="text-sm text-indigo-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-sm dark:text-indigo-400">
              View all notifications
            </Link>
          </div>
        </MenuItems>
      </transition>
    </Menu>

    <Menu as="div" class="relative z-20">
      <MenuButton
        class="-m-1.5 inline-flex items-center rounded-md p-1.5 transition-colors motion-reduce:transition-none hover:bg-stone-200/60 dark:hover:bg-stone-700/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
      >
        <span class="sr-only">Open user menu</span>
        <img class="h-8 w-8 rounded-full bg-stone-50" :src="user.profile_photo_url" alt="" />
        <span class="hidden lg:flex lg:items-center">
          <span class="ml-4 text-sm font-semibold text-stone-900 dark:text-stone-50">{{ user.name }}</span>
          <ChevronDownIcon class="ml-2 h-5 w-5 text-stone-400" aria-hidden="true" />
        </span>
      </MenuButton>
      <transition
        enter-active-class="motion-safe:transition motion-safe:ease-out motion-safe:duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="motion-safe:transition motion-safe:ease-in motion-safe:duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <MenuItems
          class="absolute right-0 z-50 mt-2.5 min-w-48 origin-top-right rounded-md border border-[var(--color-glass-border-light)] bg-[var(--color-glass-surface-strong-light)] py-2 shadow-lg backdrop-blur-glass focus:outline-none dark:border-[var(--color-glass-border-dark)] dark:bg-[var(--color-glass-surface-strong-dark)]"
        >
          <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
            <Link
              v-if="item.href !== '#'"
              :href="item.href"
              :class="[
                active ? 'bg-stone-100/70 dark:bg-stone-700/60' : '',
                'block px-3 py-2 text-sm text-stone-900 dark:text-stone-50',
              ]"
            >
              {{ item.name }}
            </Link>
            <button
              v-else
              type="button"
              :class="[
                active ? 'bg-stone-100/70 dark:bg-stone-700/60' : '',
                'block w-full px-3 py-2 text-left text-sm text-stone-900 dark:text-stone-50',
              ]"
              @click="$emit('logout')"
            >
              {{ item.name }}
            </button>
          </MenuItem>
        </MenuItems>
      </transition>
    </Menu>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { Bars3Icon, BellIcon } from '@heroicons/vue/24/outline';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { Link } from '@inertiajs/vue3';
import NotificationBody from '@/Components/NotificationBody.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

defineEmits(['open-drawer', 'logout']);

defineProps({
  user: { type: Object, required: true },
  notifications: { type: Array, default: () => [] },
  notificationCount: { type: Number, default: 0 },
});

const query = ref('');

const userNavigation = [
  { name: 'Your profile', href: '/user/profile' },
  { name: 'Query Builder', href: '/user/api-query' },
  { name: 'Settings', href: '/-/settings' },
  { name: 'Sign out', href: '#' },
];
</script>
