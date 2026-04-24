<template>
  <PillarLanding
    :pillar="pillar"
    :summary_cards="summary_cards"
    :sub_nav="sub_nav"
    tagline="Workspaces for the things you're building — tasks, research, and shared resources in one place."
  />
</template>

<script setup>
import PillarLanding from '@/Pages/Pillars/PillarLanding.vue';

defineProps({
  pillar: { type: Object, required: true },
  summary_cards: { type: Array, default: () => [] },
  sub_nav: { type: Array, default: () => [] },
});
</script>
