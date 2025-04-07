<template>
    <ServerInfrastucture title="SSH Console">
      <div class="xl:pl-96">
        <div class="px-4 py-10 sm:px-6 lg:px-8 lg:py-6">
          <pre>{{ server }}</pre>
        </div>
      </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
const { server } = defineProps({
    server: Object,
})
</script>
