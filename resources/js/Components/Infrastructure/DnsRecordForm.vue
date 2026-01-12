<template>
    <form class="space-y-4" @submit.prevent="handleSubmit">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                    Record type
                </label>
                <select
                    v-model="form.type"
                    class="mt-1 block w-full rounded-md border-stone-300 dark:border-stone-700 dark:bg-stone-800 text-sm text-stone-800 dark:text-stone-100 focus:outline-none focus:ring-stone-500 focus:border-stone-500"
                >
                    <option v-for="type in recordTypes" :key="type" :value="type">{{ type }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                    TTL (seconds)
                </label>
                <SporkInput v-model="form.ttl" type="number" min="60" step="60" class="mt-1 w-full" />
            </div>
        </div>

        <div>
            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                Name
            </label>
            <SporkInput v-model="form.name" class="mt-1 w-full" placeholder="app" />
        </div>

        <div>
            <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                Value
            </label>
            <textarea
                v-model="form.value"
                class="mt-1 block w-full rounded-md border-stone-300 dark:border-stone-700 dark:bg-stone-800 text-sm text-stone-800 dark:text-stone-100 focus:outline-none focus:ring-stone-500 focus:border-stone-500"
                rows="3"
            ></textarea>
        </div>

        <div class="flex items-center gap-2">
            <input id="proxied" v-model="form.proxied" type="checkbox" class="rounded border-stone-300 text-indigo-600 focus:ring-indigo-500" />
            <label for="proxied" class="text-sm text-stone-600 dark:text-stone-300">Proxy through Cloudflare</label>
        </div>

        <div class="flex justify-end gap-3">
            <SporkButton secondary @click.prevent="$emit('cancel')">
                Cancel
            </SporkButton>
            <SporkButton primary type="submit">
                {{ form.id ? 'Update record' : 'Create record' }}
            </SporkButton>
        </div>
    </form>
</template>

<script setup>
import { reactive, watch } from 'vue';
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";

const props = defineProps({
    record: {
        type: Object,
        default: null,
    },
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

watch(() => props.record, (record) => {
    Object.assign(form, defaultRecord(), record ?? {});
}, { immediate: true });

const handleSubmit = () => {
    emit('save', { ...form });
};
</script>

























