<template>
    <div class="w-full flex flex-col divide-y divide-stone-700 dark:divide-stone-800">
        <input
            :value="modelValue.name"
            @input="$emit('update:modelValue', {
                ...modelValue,
                name: $event.target.value
            })"
            :disabled="!editableLabel"
            :class="inputClasses(!editableLabel)"
            class="rounded-t-md text-xs leading-loose tracking-wide font-bold py-0 px-4" placeholder="text" type="text"
        />
        <label
            class="flex dark:bg-stone-600 dark:placeholder-stone-300 rounded-b-md h-10"
            v-if="type !== 'object'"
            :class="[type === 'checkbox' ? 'pl-4 pt-4': 'p-0']"
        >
            <input
                class="py-2 px-3 block shadow-sm sm:text-sm rounded-b-md"
                :class="inputClasses(disabledInput)"
                :value="modelValue.value"
                @input="$emit('update:modelValue', {
                    ...modelValue,
                    value: $event.target.value
                })"
                :type="type"
                :disabled="disabledInput"
            />

        </label>

        <div v-if="errors" class="flex flex-col">
            <div v-for="error in errors" :key="error" class="text-red-500 dark:text-red-400 px-4 text-xs py-1"> {{ error }}</div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const {
  modelValue,
  type,
  autofocus,
  disabledInput,
  editableLabel,
    error
} = defineProps({
  modelValue: Object,
  type: String,
  autofocus: Boolean,
  disabledInput: {
    type: Boolean,
    default: () => true,
  },
  editableLabel: {
    type: Boolean,
    default: () => false,
  },
    errors: Array | null
})
// emits: ['update:modelValue'],

const inputClasses = (disabled) => {
  let baseClasses = [];
  if (type !== 'checkbox') {
    baseClasses.push('w-full');
  }

  baseClasses.push('border', 'p-1');

  console.log({disabled})
  if (disabled) {
    baseClasses.push('bg-stone-50', 'border-stone-50', 'dark:border-stone-900/50', 'dark:bg-stone-700/50', 'dark:placeholder-stone-300')
  } else {
    baseClasses.push('bg-stone-50', 'border-stone-50', 'dark:border-stone-700', 'dark:bg-stone-700', 'dark:placeholder-stone-300')
  }

  return baseClasses;
};
</script>

