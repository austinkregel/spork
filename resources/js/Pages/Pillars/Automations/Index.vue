<template>
  <PillarLanding
    :pillar="pillar"
    :summary_cards="summary_cards"
    :sub_nav="sub_nav"
    tagline="Tags, conditions, and scripts that run on their own so you don't have to."
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
