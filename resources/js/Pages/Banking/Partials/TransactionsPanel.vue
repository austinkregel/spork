<script setup>
import { reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import ManualTransactionModal from '@/Components/Spork/Finance/ManualTransactionModal.vue';
import TransactionsTable from '@/Components/Spork/Finance/Transactions/TransactionsTable.vue';
import PrevNextPagination from '@/Components/Spork/Molecules/Pagination/PrevNextPagination.vue';

const props = defineProps({
  transactionsData: {
    type: Object,
    default: null,
  },
});

const manualModal = ref(null);
const openManualModal = () => manualModal.value?.open();

const filters = reactive({
  name: props.transactionsData?.filters?.filter?.name ?? '',
  tag: props.transactionsData?.filters?.filter?.tag ?? '',
});

const applyFilters = () => {
  const params = {};

  if (filters.name) {
    params['filter[name]'] = filters.name;
  }

  if (filters.tag) {
    params['filter[tag]'] = filters.tag;
  }

  router.get(route('finance.banking.transactions'), params, {
    preserveScroll: true,
    preserveState: true,
  });
};

watch(() => filters.tag, () => applyFilters());

const transactions = () => props.transactionsData?.transactions?.data ?? [];
const paginator = () => props.transactionsData?.transactions ?? {};
</script>

<template>
  <GlassSurface>
    <div class="space-y-4 p-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
          <div class="flex-1">
            <label for="transactions-search" class="sr-only">Search transactions</label>
            <GlassInput
              id="transactions-search"
              v-model="filters.name"
              type="search"
              placeholder="Search name"
              @enter="applyFilters"
            />
          </div>
          <div class="sm:w-56">
            <label for="transactions-tag-filter" class="sr-only">Filter by tag</label>
            <GlassSelect
              id="transactions-tag-filter"
              v-model="filters.tag"
            >
              <option value="">All tags</option>
              <option
                v-for="tag in transactionsData?.tags ?? []"
                :key="tag.id"
                :value="tag.name?.en ?? tag.name"
              >
                {{ tag.name?.en ?? tag.name }}
              </option>
            </GlassSelect>
          </div>
        </div>
        <GlassButton
          variant="success"
          :icon-left="PlusIcon"
          @click="openManualModal"
        >
          Add Manual Transaction
        </GlassButton>
      </div>

      <TransactionsTable :transactions="transactions()" :tags="transactionsData?.tags ?? []" />

      <PrevNextPagination :paginator="paginator()" />

      <ManualTransactionModal
        ref="manualModal"
        :accounts="transactionsData?.accounts ?? []"
        :tags="transactionsData?.tags ?? []"
      />
    </div>
  </GlassSurface>
</template>
