<template>
  <PillarLanding
    :pillar="pillar"
    :summary_cards="summary_cards"
    :sub_nav="sub_nav"
    tagline="RSS, social, and news feeds curated to keep you in the loop without the noise."
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
