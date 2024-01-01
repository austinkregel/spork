<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import {
  TagIcon,
  ServerIcon,
  LinkIcon,
    UserIcon,
  BuildingLibraryIcon,
  WalletIcon
} from "@heroicons/vue/24/outline";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";
const page = usePage();

const { tags } = defineProps({
  // pagination
  tags: Object,
})

const { data, ...pagination } = tags;
</script>

<template>
  <AppLayout title="Profile">
    <div>
      <div class="text-2xl my-8 mx-4">Tags and how they're used in your app</div>

      <div class="gap-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 mx-4">
        <div v-for="tag in data" class="flex flex-col border border-slate-600 rounded-lg py-2">
          <div class="flex flex-wrap gap-4 items-center my-2 mx-6">
            <WalletIcon class="w-8 h-8 text-blue-400" v-if="tag.type === 'finance'"/>
            <ServerIcon class="w-8 h-8 text-amber-200" v-else-if="tag.type === 'server'"/>
            <TagIcon class="w-8 h-8 text-green-400" v-else />
            <span class="text-2xl">{{ tag.name?.en }}</span>
          </div>
          <div class="flex gap-4 my-2 mx-6">
            <div class="flex gap-2">
              <ServerIcon class="w-6 h-6" />
              {{ tag.servers.length }}
            </div>
            <div class="flex gap-2">
              <LinkIcon class="w-6 h-6" />
              {{ tag.domains.length }}
            </div>
            <div class="flex gap-2">
              <UserIcon class="w-6 h-6" />
              {{ tag.people.length }}
            </div>
            <div class="flex gap-2">
              <WalletIcon class="w-6 h-6" />
              {{ tag.budgets.length }}
            </div>
          </div>
        </div>
      </div>

      <div>

Now how to apply conditions to the tags

        Sometimes tags are applied from a service, sometimes they're applied via our app.
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>

</style>
