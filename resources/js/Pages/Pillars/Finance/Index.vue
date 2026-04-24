<template>
  <PillarLanding
    :pillar="pillar"
    :summary_cards="summary_cards"
    :sub_nav="sub_nav"
    tagline="Track accounts, plan budgets, and review transactions across every connected institution."
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
