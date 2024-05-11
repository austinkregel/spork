<script setup>
import { computed } from 'vue';
const { message, self } = defineProps({
    message: Object,
    self: Object,
})

const fromSelf = computed(
    () => message.from_person?.id === self.id
)

const color = computed(() => fromSelf.value ? 'text-green-500 border border-green-500' : 'text-blue-500 border border-blue-500');
</script>

<template>
<div :class="color + ' rounded mt-2'">
    <div>{{ message.message }}</div>

    <div>{{ message?.from_person?.name}}</div>
</div>
</template>

<style scoped>

</style>
