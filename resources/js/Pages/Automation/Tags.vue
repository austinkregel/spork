<script setup>
import Manage from "@/Layouts/Manage.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { TagIcon, ServerIcon, BoltIcon, WalletIcon } from "@heroicons/vue/24/outline";
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";
import { computed, ref, watch } from "vue";

const { title, tags } = defineProps({
  title: String,
  tags: Object,
});

const { data } = tags;

const typeIcon = (type) => {
  switch (type) {
    case 'finance':
      return WalletIcon;
    case 'server':
      return ServerIcon;
    case 'automatic':
      return BoltIcon;
    default:
      return TagIcon;
  }
};

const typeLabel = (type) => {
  if (!type) {
    return 'general';
  }

  return type;
};

const isCreateOpen = ref(false);

const form = useForm({
  name: '',
  type: 'automatic',
  must_all_conditions_pass: false,
});

const typeRequiresMatchMode = computed(() => form.type === 'automatic');

watch(
  () => form.type,
  (type) => {
    if (type !== 'automatic') {
      form.must_all_conditions_pass = false;
    }
  }
);

const openCreate = () => {
  form.reset();
  form.clearErrors();
  form.type = 'automatic';
  form.must_all_conditions_pass = false;
  isCreateOpen.value = true;
};

const closeCreate = () => {
  isCreateOpen.value = false;
  form.reset();
  form.clearErrors();
};

const submitCreate = () => {
  form.post(route('automation.tags.store'), {
    preserveScroll: true,
    onSuccess: () => closeCreate(),
  });
};
</script>

<template>
  <Manage :title="title" sub-title="Automation" home="/-/automation">
    <div class="space-y-6">
      <header class="flex flex-col gap-3">
        <div class="flex items-start justify-between gap-4">
          <div class="space-y-2">
            <p class="text-sm uppercase tracking-widest text-stone-500 dark:text-stone-400">Routing logic</p>
            <h1 class="text-2xl font-semibold text-stone-900 dark:text-white">Automation tags</h1>
            <p class="text-stone-700 dark:text-stone-200 max-w-3xl">
              Tags control where crawls send data, which credentials they may use, and how often they run. Consolidating them here
              keeps automation behavior transparent and repeatable.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-2 text-sm rounded-lg bg-indigo-500 dark:bg-indigo-600 text-white shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
              @click="openCreate"
            >
              Create tag
            </button>
          </div>
        </div>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <article
          v-for="tag in data"
          :key="tag.id"
          class="border border-stone-200 dark:border-stone-800 rounded-xl p-4 bg-white dark:bg-stone-900 shadow-sm flex flex-col gap-3"
        >
          <div class="flex items-start gap-3">
            <component :is="typeIcon(tag.type)" class="w-8 h-8 text-emerald-500" />
            <div class="flex flex-col">
              <Link :href="'/-/automation/tags/' + tag.id" class="text-lg font-semibold text-stone-900 dark:text-white">
                {{ tag.name?.en }}
              </Link>
              <p class="text-sm text-stone-600 dark:text-stone-300">{{ typeLabel(tag.type) }}</p>
            </div>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-200">
            <span class="font-medium">Attached items</span>
            <span class="text-stone-900 dark:text-white">{{ tag.taggables_count }}</span>
          </div>
          <div class="flex items-center justify-between text-sm text-stone-600 dark:text-stone-200">
            <span class="font-medium">Transaction total</span>
            <span class="text-stone-900 dark:text-white">${{ (Math.round((tag.transactions_sum_amount ?? 0) * 100) / 100).toFixed(2) }}</span>
          </div>
        </article>
      </div>
    </div>
  </Manage>

  <TransitionRoot appear :show="isCreateOpen" as="template">
    <Dialog as="div" class="relative z-50" @close="closeCreate">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/50" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-stone-900 text-left align-middle shadow-xl transition-all border border-stone-200 dark:border-stone-800"
            >
              <div class="px-6 py-4 border-b border-stone-200 dark:border-stone-800 flex items-center justify-between">
                <DialogTitle class="text-lg font-medium text-stone-900 dark:text-white">
                  Create tag
                </DialogTitle>
                <button
                  type="button"
                  @click="closeCreate"
                  class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white text-sm"
                >
                  Close
                </button>
              </div>

              <form class="px-6 py-6 space-y-4" @submit.prevent="submitCreate">
                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    placeholder="e.g. subscriptions"
                    class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                  />
                  <div v-if="form.errors.name" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.name }}</div>
                </div>

                <div class="space-y-1">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Type</label>
                  <select
                    v-model="form.type"
                    class="w-full rounded-lg bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 px-3 py-2 text-stone-900 dark:text-white text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-stone-500 focus:border-stone-500"
                  >
                    <option value="automatic">automatic</option>
                    <option value="finance">finance</option>
                    <option value="server">server</option>
                    <option value="">general</option>
                  </select>
                  <div v-if="form.errors.type" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.type }}</div>
                </div>

                <div class="space-y-1" :class="typeRequiresMatchMode ? '' : 'opacity-60 pointer-events-none'">
                  <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Match mode</label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label class="flex items-start gap-2 rounded-lg border border-stone-200 dark:border-stone-800 p-3 cursor-pointer bg-white dark:bg-stone-900">
                      <input
                        v-model="form.must_all_conditions_pass"
                        :value="false"
                        type="radio"
                        class="mt-1"
                      />
                      <div>
                        <div class="text-sm font-medium text-stone-900 dark:text-white">Any condition</div>
                        <div class="text-xs text-stone-600 dark:text-stone-300">Apply when at least one condition matches.</div>
                      </div>
                    </label>
                    <label class="flex items-start gap-2 rounded-lg border border-stone-200 dark:border-stone-800 p-3 cursor-pointer bg-white dark:bg-stone-900">
                      <input
                        v-model="form.must_all_conditions_pass"
                        :value="true"
                        type="radio"
                        class="mt-1"
                      />
                      <div>
                        <div class="text-sm font-medium text-stone-900 dark:text-white">All conditions</div>
                        <div class="text-xs text-stone-600 dark:text-stone-300">Apply only when every condition matches.</div>
                      </div>
                    </label>
                  </div>
                  <div v-if="form.errors.must_all_conditions_pass" class="text-xs text-red-500 dark:text-red-400">{{ form.errors.must_all_conditions_pass }}</div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                  <button
                    type="button"
                    @click="closeCreate"
                    class="px-4 py-2 text-sm rounded-lg border border-stone-300 dark:border-stone-700 text-stone-600 dark:text-stone-300 bg-white dark:bg-stone-900"
                    :disabled="form.processing"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="px-4 py-2 text-sm rounded-lg bg-indigo-500 hover:bg-indigo-600 text-white disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-stone-900"
                    :disabled="form.processing"
                  >
                    Create
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
