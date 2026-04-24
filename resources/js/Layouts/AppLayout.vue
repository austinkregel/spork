<script setup>
import GlassAppLayout from '@/Layouts/GlassAppLayout.vue';

defineProps({
  title: String,
  navigation: Array,
  subNavigation: Array,
  current_navigation: Object,
});
</script>

<template>
  <GlassAppLayout :title="title">
    <template v-if="$slots.header" #header>
      <slot name="header" />
    </template>
    <slot />
  </GlassAppLayout>
</template>
