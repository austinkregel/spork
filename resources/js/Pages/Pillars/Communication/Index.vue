<template>
  <PillarLanding
    :pillar="pillar"
    :summary_cards="summary_cards"
    :sub_nav="sub_nav"
    tagline="Email, chat, calendar, and contacts — every conversation in one home."
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
