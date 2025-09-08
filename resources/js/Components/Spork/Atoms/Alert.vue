<template>
  <div
    :class="[
      'flex items-start gap-4 rounded-lg p-4 border text-sm',
      alertTypeClass,
      'transition-colors',
    ]"
    role="alert"
  >
    <div v-if="icon" class="shrink-0 mt-1">
      <slot name="icon">
        <component :is="icon" />
      </slot>
    </div>
    <div class="flex-1">
      <slot />
    </div>
    <button
      v-if="dismissible"
      @click="$emit('close')"
      type="button"
      class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
      aria-label="Dismiss"
    >
      <slot name="close">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </slot>
    </button>
  </div>
</template>

<script setup>
import { computed, defineProps } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: 'info', // info, success, warning, error, upgrade
    validator: (val) => ['info', 'success', 'warning', 'error', 'upgrade'].includes(val),
  },
  icon: {
    type: [String, Object],
    default: null,
  },
  dismissible: {
    type: Boolean,
    default: false,
  },
});

const alertTypeClass = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-green-50 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-100 dark:border-green-800';
    case 'warning':
      return 'bg-yellow-50 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-100 dark:border-yellow-800';
    case 'error':
      return 'bg-red-50 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-100 dark:border-red-800';
    case 'upgrade':
      return 'bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-100 dark:border-indigo-800';
    default:
      return 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-100 dark:border-blue-800';
  }
});
</script>

<!-- Usage Example:
<Alert type="success" :icon="SuccessIcon">Success message!</Alert>
<Alert type="error" :icon="ErrorIcon">Error message!</Alert>
<Alert type="warning" :icon="WarningIcon">Warning message!</Alert>
<Alert type="info" :icon="InfoIcon">Info message!</Alert>
<Alert type="upgrade" :icon="UpgradeIcon">Upgrade message!</Alert>
<Alert type="info" dismissible @close="handleClose">Dismissible alert!</Alert>
-->

