<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  tags: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Select tags',
  },
  error: {
    type: String,
    default: '',
  },
  selectClass: {
    type: String,
    default:
      'w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white h-28 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500',
  },
});

const emit = defineEmits(['update:modelValue']);

const selected = computed({
  get: () => props.modelValue ?? [],
  set: (value) => emit('update:modelValue', value ?? []),
});
</script>

<template>
  <div class="space-y-1">
    <select v-model="selected" multiple :class="selectClass">
      <option v-if="tags.length === 0" disabled value="">{{ placeholder }}</option>
      <option v-for="tag in tags" :key="tag.id" :value="tag.id">
        {{ tag.name?.en ?? tag.name }}
      </option>
    </select>
    <div v-if="error" class="text-xs text-red-500 dark:text-red-400">{{ error }}</div>
  </div>
</template>





