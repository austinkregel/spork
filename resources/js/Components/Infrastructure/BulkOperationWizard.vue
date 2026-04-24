<template>
  <div class="space-y-4">
    <div class="grid gap-4 lg:grid-cols-3">
      <div class="space-y-2">
        <button
          v-for="operation in operations"
          :key="operation.id"
          type="button"
          :aria-pressed="selectedOperation?.id === operation.id"
          :class="[
            'w-full rounded-lg border px-4 py-3 text-left transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950',
            selectedOperation?.id === operation.id
              ? 'border-indigo-500 bg-indigo-500/10 text-indigo-700 dark:text-indigo-200'
              : 'border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass text-stone-700 dark:text-stone-200 hover:bg-stone-100/60 dark:hover:bg-stone-800/40',
          ]"
          @click="selectOperation(operation)"
        >
          <p class="text-sm font-semibold">{{ operation.label }}</p>
          <p class="text-xs">{{ operation.description }}</p>
        </button>
      </div>

      <GlassSurface class="space-y-4 p-4 lg:col-span-2">
        <div class="grid gap-4 md:grid-cols-2">
          <GlassField v-slot="{ id, describedby, invalid }" label="Target scope">
            <GlassSelect :id="id" v-model="form.scope" :invalid="invalid" :describedby="describedby">
              <option value="domains">Domains</option>
              <option value="servers">Servers</option>
            </GlassSelect>
          </GlassField>
          <GlassField v-slot="{ id, describedby, invalid }" label="Filter tags">
            <GlassInput :id="id" v-model="form.filter" placeholder="finance, production" :invalid="invalid" :describedby="describedby" />
          </GlassField>
        </div>

        <div class="space-y-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
            {{ selectedOperation?.label ?? 'Choose an operation' }} inputs
          </p>
          <GlassField
            v-for="field in selectedOperation?.fields ?? []"
            :key="field.id"
            v-slot="{ id, describedby, invalid }"
            :label="field.label"
          >
            <component
              :is="field.component"
              :id="id"
              v-model="form.payload[field.id]"
              :invalid="invalid"
              :describedby="describedby"
              v-bind="field.props"
            />
          </GlassField>
        </div>

        <div class="flex justify-end gap-2">
          <GlassButton variant="secondary" @click="$emit('cancel')">Cancel</GlassButton>
          <GlassButton @click="submit">Queue operation</GlassButton>
        </div>
      </GlassSurface>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';

const props = defineProps({
  operations: { type: Array, default: () => [] },
});

const emit = defineEmits(['submit', 'cancel']);

const createDefaultForm = () => ({
  scope: 'domains',
  filter: '',
  payload: {},
});

const selectedOperation = ref(props.operations[0] ?? {});
const form = reactive(createDefaultForm());

watch(
  () => props.operations,
  (operations) => {
    if (!operations.length) {
      selectedOperation.value = {};
      return;
    }
    if (!operations.find((operation) => operation.id === selectedOperation.value.id)) {
      selectedOperation.value = operations[0];
    }
  },
  { immediate: true },
);

function selectOperation(operation) {
  selectedOperation.value = operation;
  form.payload = {};
}

function submit() {
  emit('submit', {
    operation: selectedOperation.value.id,
    scope: form.scope,
    filter: form.filter,
    payload: form.payload,
  });
  Object.assign(form, createDefaultForm());
}
</script>
