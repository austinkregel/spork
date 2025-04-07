<template>
  <AppLayout title="Dashboard">
    <div class="flex flex-wrap gap-4 m-4">

      <aside class="fixed inset-y-0 left-72 hidden w-96 overflow-y-auto border-r border-gray-200 dark:border-gray-700 px-4 py-6 sm:px-6 lg:px-8 xl:block mt-16">
        <div class="w-full tracking-widest font-bold text-stone-600 dark:text-stone-50 uppercase ml-8 mb-8">Server</div>

        <div class="flex flex-col gap-4">
          <Link
              v-for="item in navigation"
              :href="item.href"
              class="text-white w-full flex flex-wrap gap-2"
              :class="item.href === url ? 'bg-gray-900 dark:bg-gray-800 text-stone-50' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-400'"
          >
            <DynamicIcon v-if="item.icon" :icon-name="item.icon" class="w-6 h-6 outline-current" :active="item.href === url" />
            {{item.name}}

          </Link>
        </div>

      </aside>


      <main class="lg:pl-72">
        <slot />
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link } from '@inertiajs/vue3'
import DynamicIcon from "@/Components/DynamicIcon.vue";
import {ref} from "vue";
const { server } = defineProps({
  server: Object,
})
const url = ref(window.location.pathname);

const navigation = [
  { name: 'SSH Console', href: '/-/servers/'+server.id+'/console', icon: 'CommandLineIcon' },
  { name: 'SSH Keys', href: '/-/servers/'+server.id+'/keys', icon: 'KeyIcon' },
  { name: 'Supervised Workers', href: '/-/servers/'+server.id+'/workers', icon: 'QueueListIcon' },
  { name: 'Crontab', href: '/-/servers/'+server.id+'/crontab', icon: 'ClockIcon' },
  { name: 'Logs', href: '/-/servers/'+server.id+'/logs', icon: 'Square3Stack3DIcon' },
]
</script>
