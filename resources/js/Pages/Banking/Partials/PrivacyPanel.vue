<script setup>
import { router } from '@inertiajs/vue3';
import MetricCard from "@/Components/Spork/Atoms/MetricCard.vue";
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/outline";

const props = defineProps({
  privacyData: {
    type: Object,
    default: null,
  },
});

const recentCards = () => props.privacyData?.recent_cards ?? [];

const transactions = () => props.privacyData?.transactions?.data ?? [];
const txPaginator = () => props.privacyData?.transactions ?? {};

const currency = (cents) => Number((cents ?? 0) / 100).toLocaleString('en-US', { style: 'currency', currency: 'USD' });

const summary = () => props.privacyData?.summary ?? {};

const shortToken = (token) => {
  if (!token) return '';
  const s = String(token);
  return s.length <= 8 ? s : `${s.slice(0, 4)}…${s.slice(-4)}`;
};

const last4 = (token) => {
  if (!token) return '';
  const s = String(token);
  return s.length <= 4 ? s : s.slice(-4);
};

const tagsFor = (model) => {
  return model?.tags ?? [];
};

const cardsScroller = ref(null);
const scrollLeft = ref(0);
const scrollWidth = ref(0);
const clientWidth = ref(0);

const canScrollLeft = computed(() => scrollLeft.value > 4);
const canScrollRight = computed(() => scrollLeft.value + clientWidth.value < scrollWidth.value - 4);

let resizeObserver = null;

const syncScrollState = () => {
  const el = cardsScroller.value;
  if (!el) return;
  scrollLeft.value = el.scrollLeft ?? 0;
  scrollWidth.value = el.scrollWidth ?? 0;
  clientWidth.value = el.clientWidth ?? 0;
};

const scrollCardsBy = (direction) => {
  const el = cardsScroller.value;
  if (!el) return;
  // Scroll roughly one card (plus gap) at a time.
  const delta = Math.max(320, Math.round((el.clientWidth ?? 0) * 0.6));
  el.scrollBy({
    left: direction === 'left' ? -delta : delta,
    behavior: 'smooth',
  });
};

onMounted(async () => {
  await nextTick();
  syncScrollState();
  const el = cardsScroller.value;
  if (!el) return;

  el.addEventListener('scroll', syncScrollState, { passive: true });

  resizeObserver = new ResizeObserver(() => syncScrollState());
  resizeObserver.observe(el);
});

onBeforeUnmount(() => {
  const el = cardsScroller.value;
  if (el) {
    el.removeEventListener('scroll', syncScrollState);
  }
  if (resizeObserver) {
    try {
      resizeObserver.disconnect();
    } catch (e) {
      // no-op
    }
    resizeObserver = null;
  }
});
</script>

<template>
  <div class="space-y-6 p-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      <MetricCard title="Open Cards" :value="summary().open_cards_count ?? 0" :loading="false" />
      <MetricCard title="Tx (30d)" :value="summary().transactions_30d_count ?? 0" :loading="false" />
      <MetricCard title="Pending (30d)" :value="summary().pending_30d_count ?? 0" :loading="false" />
      <MetricCard title="Declined (30d)" :value="summary().declined_30d_count ?? 0" :loading="false" />
      <MetricCard
        title="Approved (30d)"
        :value="currency(summary().settled_approved_30d_sum_cents ?? 0)"
        :loading="false"
      />
    </div>

    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-stone-200 dark:border-stone-800">
          <div class="text-sm font-semibold text-stone-900 dark:text-white">Recently used cards</div>
          <div class="text-xs text-stone-500 dark:text-stone-400">Most recent activity across your open Privacy cards.</div>
        </div>
        <div class="p-4">
          <div class="relative">
            <button
              v-if="canScrollLeft"
              type="button"
              class="absolute left-2 top-1/2 -translate-y-1/2 z-10 rounded-full border border-stone-200 dark:border-stone-800 bg-white/90 dark:bg-stone-950/90 text-stone-700 dark:text-stone-200 shadow-sm px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900 cursor-pointer"
              @click="scrollCardsBy('left')"
              aria-label="Scroll cards left"
            >
              <ChevronLeftIcon class="h-5 w-5" />
            </button>
            <button
              v-if="canScrollRight"
              type="button"
              class="absolute right-2 top-1/2 -translate-y-1/2 z-10 rounded-full border border-stone-200 dark:border-stone-800 bg-white/90 dark:bg-stone-950/90 text-stone-700 dark:text-stone-200 shadow-sm px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900 cursor-pointer"
              @click="scrollCardsBy('right')"
              aria-label="Scroll cards right"
            >
              <ChevronRightIcon class="h-5 w-5" />
            </button>

            <div
              v-if="canScrollLeft"
              class="pointer-events-none absolute left-0 top-0 h-full w-10 bg-gradient-to-r from-white dark:from-stone-900 to-transparent"
            />
            <div
              v-if="canScrollRight"
              class="pointer-events-none absolute right-0 top-0 h-full w-10 bg-gradient-to-l from-white dark:from-stone-900 to-transparent"
            />

            <div ref="cardsScroller" class="flex gap-4 overflow-x-auto pb-2 scroll-smooth">
            <div
              v-for="card in recentCards()"
              :key="card.id"
              class="relative shrink-0 w-[22rem] rounded-2xl border border-stone-200 dark:border-stone-800 shadow-sm overflow-hidden bg-gradient-to-br from-stone-50 via-white to-stone-100 dark:from-stone-800 dark:via-stone-800 dark:to-stone-950"
            >
              <!-- subtle shine -->
              <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent dark:via-white/5 pointer-events-none" />

              <div class="relative p-4 aspect-[1.586/1] flex flex-col justify-between">
                <div class="flex items-start justify-between gap-4">
                  <div class="min-w-0">
                    <div class="text-xs uppercase tracking-widest text-stone-500 dark:text-stone-400">
                      Privacy card
                    </div>
                    <div class="mt-1 text-base font-semibold text-stone-900 dark:text-white truncate">
                      {{ card.memo || card.descriptor || 'Unnamed card' }}
                    </div>
                  </div>
                  <div class="text-right">
                    <div v-if="card.spend_limit_cents !== null" class="text-sm font-semibold text-stone-900 dark:text-white">
                      {{ currency(card.spend_limit_cents) }}
                    </div>
                    <div v-if="card.spend_limit_duration" class="text-xs text-stone-500 dark:text-stone-400">
                      {{ card.spend_limit_duration }}
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div class="h-10 w-14 rounded-lg bg-gradient-to-br from-amber-200 to-amber-400 dark:from-amber-300/30 dark:to-amber-500/30 border border-amber-300/60 dark:border-amber-400/20" />
                  <div class="flex flex-wrap gap-2 justify-end">
                    <span v-if="card.state" class="inline-flex px-2 py-1 rounded-full text-xs bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200">
                      {{ card.state }}
                    </span>
                    <span v-if="card.type" class="inline-flex px-2 py-1 rounded-full text-xs bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200">
                      {{ card.type }}
                    </span>
                  </div>
                </div>

                <div class="flex items-end justify-between">
                  <div class="text-sm font-mono tracking-widest text-stone-800 dark:text-stone-100">
                    •••• •••• •••• {{ last4(card.card_token) }}
                  </div>
                  <div class="text-xs text-stone-500 dark:text-stone-400 text-right">
                    <div class="uppercase tracking-wide">Last used</div>
                    <div class="font-medium text-stone-700 dark:text-stone-200">
                      {{ card.last_used_at ? String(card.last_used_at).slice(0, 10) : '—' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          </div>

          <div v-if="recentCards().length === 0" class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">
            No recent Privacy card activity found.
          </div>
        </div>
    </div>

    <div class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-stone-200 dark:border-stone-800">
          <div class="text-sm font-semibold text-stone-900 dark:text-white">Transactions</div>
          <div class="text-xs text-stone-500 dark:text-stone-400">Privacy transaction feed.</div>
        </div>
        <div class="divide-y divide-stone-200 dark:divide-stone-800">
          <div v-for="tx in transactions()" :key="tx.id" class="px-4 py-3 flex items-center justify-between gap-4">
            <div class="min-w-0">
              <div class="font-medium text-stone-900 dark:text-white truncate">
                {{ tx.memo || tx.descriptor || tx.privacy_transaction_id }}
              </div>
              <div class="text-xs text-stone-500 dark:text-stone-400">
                <span v-if="tx.status">{{ tx.status }}</span>
                <span v-if="tx.result" class="ml-2">{{ tx.result }}</span>
                <span v-if="tx.mcc" class="ml-2">MCC {{ tx.mcc }}</span>
              </div>
              <div v-if="tagsFor(tx).length" class="mt-1">
                <span
                  v-for="tag in tagsFor(tx)"
                  :key="tag.id"
                  class="inline-flex text-xs px-2 py-1 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200 mr-2"
                >
                  {{ tag.name?.en ?? tag.name }}
                </span>
              </div>
            </div>
            <div class="text-right font-semibold text-stone-900 dark:text-white">
              {{ currency(tx.amount_cents) }}
            </div>
          </div>
          <div v-if="transactions().length === 0" class="px-4 py-6 text-center text-sm text-stone-500 dark:text-stone-400">
            No Privacy transactions found.
          </div>
        </div>
        <div class="px-4 py-3 border-t border-stone-200 dark:border-stone-800 flex items-center justify-between text-sm text-stone-500 dark:text-stone-400">
          <button
            v-if="txPaginator().prev_page_url"
            @click="router.visit(txPaginator().prev_page_url, { preserveScroll: true, preserveState: true })"
            class="px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-600 dark:text-stone-300 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
          >
            Previous
          </button>
          <span v-else />
          <button
            v-if="txPaginator().next_page_url"
            @click="router.visit(txPaginator().next_page_url, { preserveScroll: true, preserveState: true })"
            class="px-3 py-2 rounded-lg border border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 text-stone-600 dark:text-stone-300 shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
          >
            Next
          </button>
        </div>
      </div>
  </div>
</template>


