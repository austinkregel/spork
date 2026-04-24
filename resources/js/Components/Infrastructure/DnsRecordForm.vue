<template>
  <form class="space-y-4" @submit.prevent="handleSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <GlassField v-slot="{ id, describedby, invalid }" label="Record type">
        <GlassSelect :id="id" v-model="form.type" :invalid="invalid" :describedby="describedby">
          <option v-for="type in recordTypes" :key="type" :value="type">{{ type }}</option>
        </GlassSelect>
      </GlassField>
      <GlassField v-slot="{ id, describedby, invalid }" label="TTL (seconds)">
        <GlassInput :id="id" v-model="form.ttl" type="number" min="60" step="60" :invalid="invalid" :describedby="describedby" />
      </GlassField>
    </div>

    <GlassField v-slot="{ id, describedby, invalid }" label="Name">
      <GlassInput :id="id" v-model="form.name" placeholder="app" :invalid="invalid" :describedby="describedby" />
    </GlassField>

    <GlassField label="Value">
      <textarea
        v-model="form.value"
        rows="3"
        class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500"
      />
    </GlassField>

    <label class="inline-flex items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
      <input
        v-model="form.proxied"
        type="checkbox"
        class="h-4 w-4 rounded border-stone-300 text-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-stone-50 dark:border-stone-600 dark:bg-stone-800 dark:focus:ring-offset-stone-950"
      />
      <span>Proxy through Cloudflare</span>
    </label>

    <div class="flex justify-end gap-2">
      <GlassButton variant="secondary" type="button" @click="$emit('cancel')">Cancel</GlassButton>
      <GlassButton type="submit">{{ form.id ? 'Update record' : 'Create record' }}</GlassButton>
    </div>
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';

const props = defineProps({
  record: { type: Object, default: null },
});

const emit = defineEmits(['save', 'cancel']);

const defaultRecord = () => ({
  id: null,
  type: 'A',
  name: '',
  value: '',
  ttl: 300,
  proxied: false,
});

const form = reactive(defaultRecord());

const recordTypes = ['A', 'AAAA', 'CNAME', 'TXT', 'MX', 'NS', 'SRV', 'CAA'];

watch(
  () => props.record,
  (record) => {
    Object.assign(form, defaultRecord(), record ?? {});
  },
  { immediate: true },
);

function handleSubmit() {
  emit('save', { ...form });
}
</script>
