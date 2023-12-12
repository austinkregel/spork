<script setup>

import Manage from "@/Layouts/Manage.vue";

defineProps({
  title: String,
})
</script>

<template>
  <Manage :title="title">

  </Manage>
</template>

<style scoped>

</style>
