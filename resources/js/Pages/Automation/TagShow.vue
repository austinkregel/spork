<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { TagIcon, ServerIcon, BoltIcon, WalletIcon } from "@heroicons/vue/24/outline";
import ConditionsEditor from "@/Components/Spork/Molecules/ConditionsEditor.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassTable from "@/Components/Glass/GlassTable.vue";
import GlassField from "@/Components/Glass/GlassField.vue";
import GlassInput from "@/Components/Glass/GlassInput.vue";
import GlassSelect from "@/Components/Glass/GlassSelect.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import dayjs from "dayjs";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    title: String,
    tag: Object,
    type: String,
    condition_parameter_groups: {
        type: Array,
        default: () => [],
    },
});

const typeIcon = (type) => {
    switch (type) {
        case 'finance': return WalletIcon;
        case 'server': return ServerIcon;
        case 'automatic': return BoltIcon;
        default: return TagIcon;
    }
};

const typeLabel = (type) => type || 'general';
const date = (d) => dayjs(d).format('YYYY-MM-DD');
const currency = (value) => Number(value ?? 0).toLocaleString('en-US', { style: 'currency', currency: 'USD' });

const form = useForm({
    name: props.tag?.name?.en ?? '',
    type: props.tag?.type ?? '',
    must_all_conditions_pass: !!props.tag?.must_all_conditions_pass,
});

watch(
    () => form.type,
    (type) => {
        if (type !== 'automatic') {
            form.must_all_conditions_pass = false;
        }
    },
);

const typeRequiresMatchMode = computed(() => form.type === 'automatic');

const saveTag = () => {
    form.patch(route('automations.tags.update', props.tag?.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :title="title">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard>
                <div class="flex items-start gap-3">
                    <component :is="typeIcon(tag.type)" class="h-9 w-9 text-emerald-500" aria-hidden="true" />
                    <div>
                        <h1 class="text-2xl font-semibold text-stone-900 dark:text-stone-50">{{ tag.name?.en }}</h1>
                        <p class="text-sm text-stone-600 dark:text-stone-300">Type: {{ typeLabel(tag.type) }}</p>
                        <p class="text-sm text-stone-600 dark:text-stone-300">Attached items: {{ tag.taggables_count ?? 0 }}</p>
                    </div>
                </div>
                <p class="mt-3 max-w-3xl text-stone-700 dark:text-stone-200">
                    Tune how this tag routes automation output or throttles access. Conditions describe what should be tagged; downstream
                    playbooks can use the same tag to pick credentials, pacing, and notification rules.
                </p>
            </GlassCard>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <GlassSurface class="overflow-hidden xl:col-span-2">
                    <ConditionsEditor :conditions="tag.conditions" :type="type" :id="tag?.id" :parameter-groups="condition_parameter_groups" />
                </GlassSurface>

                <GlassCard
                    title="Tag settings"
                    subtitle="Name, type, and match behavior for conditional tagging."
                >
                    <form class="space-y-3" @submit.prevent="saveTag">
                        <GlassField label="Name" :error="form.errors.name">
                            <GlassInput v-model="form.name" :invalid="!!form.errors.name" />
                        </GlassField>

                        <GlassField label="Type" :error="form.errors.type">
                            <GlassSelect v-model="form.type" :invalid="!!form.errors.type">
                                <option value="automatic">automatic</option>
                                <option value="finance">finance</option>
                                <option value="server">server</option>
                                <option value="">general</option>
                            </GlassSelect>
                        </GlassField>

                        <fieldset :class="['space-y-2', typeRequiresMatchMode ? '' : 'pointer-events-none opacity-60']">
                            <legend class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Match mode</legend>
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] p-3">
                                <input v-model="form.must_all_conditions_pass" :value="false" type="radio" class="mt-1">
                                <div>
                                    <div class="text-sm font-medium text-stone-900 dark:text-stone-50">Any condition</div>
                                    <div class="text-xs text-stone-600 dark:text-stone-300">Apply when at least one condition matches.</div>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] p-3">
                                <input v-model="form.must_all_conditions_pass" :value="true" type="radio" class="mt-1">
                                <div>
                                    <div class="text-sm font-medium text-stone-900 dark:text-stone-50">All conditions</div>
                                    <div class="text-xs text-stone-600 dark:text-stone-300">Apply only when every condition matches.</div>
                                </div>
                            </label>
                            <p v-if="form.errors.must_all_conditions_pass" class="text-xs text-red-500 dark:text-red-400">
                                {{ form.errors.must_all_conditions_pass }}
                            </p>
                        </fieldset>

                        <div class="flex items-center justify-end">
                            <GlassButton type="submit" :disabled="form.processing">Save</GlassButton>
                        </div>
                    </form>

                    <dl class="mt-4 space-y-2 text-sm text-stone-600 dark:text-stone-300">
                        <div class="flex items-center justify-between">
                            <dt class="font-semibold text-stone-900 dark:text-stone-50">Transaction total</dt>
                            <dd class="text-stone-900 dark:text-stone-50">${{ (Math.round((tag.transactions_sum_amount ?? 0) * 100) / 100).toFixed(2) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="font-semibold text-stone-900 dark:text-stone-50">Linked feeds</dt>
                            <dd class="text-stone-900 dark:text-stone-50">{{ tag.feeds?.length ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="font-semibold text-stone-900 dark:text-stone-50">Servers</dt>
                            <dd class="text-stone-900 dark:text-stone-50">{{ tag.servers?.length ?? 0 }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="font-semibold text-stone-900 dark:text-stone-50">Projects</dt>
                            <dd class="text-stone-900 dark:text-stone-50">{{ tag.projects?.length ?? 0 }}</dd>
                        </div>
                    </dl>
                </GlassCard>
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <GlassTable
                    header="Transactions"
                    description="Recent transactions carrying this tag"
                    :items="tag.transactions"
                    :headers="[
                        { name: 'Name', accessor: (item) => item.name },
                        { name: 'Amount', accessor: (item) => item.amount },
                        { name: 'Pending', accessor: (item) => item.pending ? 'pending' : 'posted' },
                        { name: 'Date', accessor: (item) => date(item.date) },
                        { name: 'Category', accessor: (item) => item.personal_finance_category },
                    ]"
                    empty-message="No transactions tagged yet."
                />

                <GlassTable
                    header="Articles"
                    description="Recent articles matched by this tag"
                    :items="tag.articles"
                    :headers="[
                        { name: 'Headline', accessor: (item) => item.headline },
                        { name: 'Updated', accessor: (item) => date(item.last_modified) },
                    ]"
                    empty-message="No articles tagged yet."
                />
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <GlassTable
                    header="Servers"
                    description="Servers carrying this tag"
                    :items="tag.servers"
                    :headers="[
                        { name: 'Name', accessor: (item) => item.name },
                    ]"
                    empty-message="No servers tagged yet."
                />

                <GlassTable
                    header="Budgets"
                    description="Budget rules linked to this tag"
                    :items="tag.budgets"
                    :headers="[
                        { name: 'Budget', accessor: (item) => item.name },
                        { name: 'Limit', accessor: (item) => currency(item.amount) },
                    ]"
                    empty-message="No budgets tagged yet."
                />
            </section>
        </div>
    </AppLayout>
</template>
