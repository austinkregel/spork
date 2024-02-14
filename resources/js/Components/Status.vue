<script setup>
  import { computed } from 'vue';

  const { status } = defineProps({
    status: String,
  })

  const color = computed(() => {
    switch (status) {
      case 'todo':
        return 'text-red-500 border-red-500';

      case 'in progress':
        return 'text-blue-500 border-blue-500';

      case 'done':
        return 'text-green-500 border-green-500';
    }
  });
</script>

<template>
  <div :class="color" class="border inline p-1 rounded">
    {{status}}
  </div>
</template>
