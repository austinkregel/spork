<script setup>
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Make feed public',
  },
  message: {
    type: String,
    default:
      'Making a feed public shares it with all users. This can expose tags/conditions you may consider private. Continue?',
  },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
  <ConfirmationModal :show="show" @close="emit('close')">
    <template #title>
      {{ title }}
    </template>

    <template #content>
      {{ message }}
    </template>

    <template #footer>
      <SecondaryButton @click="emit('close')">
        Cancel
      </SecondaryButton>

      <DangerButton class="ml-3" @click="emit('confirm')">
        Make Public
      </DangerButton>
    </template>
  </ConfirmationModal>
</template>

