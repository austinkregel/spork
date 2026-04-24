<template>
  <div class="flex h-screen min-h-0 flex-col bg-stone-50 dark:bg-stone-950">
    <Head :title="title" />
    <Banner />

    <GlassDrawer :open="drawerOpen" :pillars="pillars" @close="drawerOpen = false" />

    <div class="flex min-h-0 flex-1">
      <GlassRail :pillars="pillars" :current_pillar="current_pillar" />
      <GlassSecondaryPanel :items="sub_nav" />

      <div class="flex min-h-0 min-w-0 flex-1 flex-col">
        <GlassTopBar
          :user="user"
          :notifications="notifications"
          :notification-count="notificationCount"
          @open-drawer="drawerOpen = true"
          @logout="logout"
        />

        <main class="min-h-0 flex-1 overflow-auto">
          <div
            v-if="$slots.header"
            class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] px-4 py-3 backdrop-blur-sm"
          >
            <slot name="header" />
          </div>
          <slot />
        </main>
      </div>
    </div>

    <audio id="glitch-sound" src="/sounds/glitch-in-the-matrix-600.ogg" preload="auto" type="audio/ogg" />
    <audio id="finished-sound" src="/sounds/just-saying-593.ogg" preload="auto" type="audio/ogg" />
    <audio id="error-sound" src="/sounds/relentless-572.ogg" preload="auto" type="audio/ogg" />
    <audio id="notification-sound" src="/sounds/swiftly-610.ogg" preload="auto" type="audio/ogg" />
    <audio id="success-sound" src="/sounds/i-did-it-message-tone.ogg" preload="auto" type="audio/ogg" />
    <audio id="achievement-sound" src="/sounds/achievement-message-tone.ogg" preload="auto" type="audio/ogg" />

    <NewMessageToastCenter />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import GlassDrawer from '@/Components/Glass/GlassDrawer.vue';
import GlassRail from '@/Components/Glass/GlassRail.vue';
import GlassSecondaryPanel from '@/Components/Glass/GlassSecondaryPanel.vue';
import GlassTopBar from '@/Components/Glass/GlassTopBar.vue';
import NewMessageToastCenter from '@/Components/Spork/Molecules/Conversations/NewMessageToastCenter.vue';

defineProps({
  title: String,
});

const page = usePage();
const drawerOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const pillars = computed(() => page.props.pillars ?? []);
const current_pillar = computed(() => page.props.current_pillar);
const sub_nav = computed(() => page.props.sub_nav ?? []);
const notifications = computed(() => page.props.notifications ?? []);

const notificationCount = computed(
  () => (page.props.unread_email_count ?? 0) + (page.props.notification_count ?? 0),
);

function logout() {
  router.post(route('logout'));
}
</script>
