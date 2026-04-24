<template>
  <input
    :id="id"
    :type="type"
    :name="name"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    :autocomplete="autocomplete"
    :aria-invalid="invalid || undefined"
    :aria-describedby="describedby"
    :class="classes"
    @input="onInput"
    @keyup.enter="$emit('enter', $event)"
  />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  id: { type: String, default: null },
  name: { type: String, default: null },
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  autocomplete: { type: String, default: null },
  invalid: { type: Boolean, default: false },
  describedby: { type: String, default: null },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
});

const emit = defineEmits(['update:modelValue', 'enter']);

function onInput(event) {
  emit('update:modelValue', event.target.value);
}

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'px-2.5 py-1 text-xs';
    case 'lg':
      return 'px-4 py-2.5 text-base';
    default:
      return 'px-3 py-2 text-sm';
  }
});

const stateClass = computed(() => {
  if (props.invalid) {
    return 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500';
  }
  return 'border-stone-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-stone-600 dark:focus:border-indigo-400 dark:focus:ring-indigo-400';
});

const classes = computed(() => [
  'block w-full rounded-md border bg-white/70 text-stone-900 shadow-sm transition-colors motion-reduce:transition-none',
  'placeholder:text-stone-400 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500',
  'focus:outline-none focus:ring-2',
  sizeClass.value,
  stateClass.value,
  props.disabled ? 'opacity-50 cursor-not-allowed bg-stone-100 dark:bg-stone-800' : '',
]);
</script>
