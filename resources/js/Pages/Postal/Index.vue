<template>
  <AppLayout title="Messages">
    <div class="flex h-[calc(100vh-4rem)] min-h-0 w-full">
      <aside
        class="flex w-full max-w-sm shrink-0 flex-col border-r border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass xl:w-96"
        aria-label="Threads"
      >
        <header class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-3">
          <h1 class="text-base font-semibold text-stone-900 dark:text-stone-50">Conversations</h1>
          <p class="text-xs text-stone-500 dark:text-stone-400">{{ totalLabel }}</p>
        </header>

        <ul
          v-if="threads.length"
          class="min-h-0 flex-1 divide-y divide-[var(--color-glass-border-light)] overflow-y-auto dark:divide-[var(--color-glass-border-dark)]"
        >
          <li v-for="thread in threads" :key="thread.id">
            <Link
              :href="route('communication.chat.show', thread.id)"
              class="flex flex-col gap-1 px-4 py-3 transition-colors motion-reduce:transition-none hover:bg-stone-100/60 dark:hover:bg-stone-800/40 focus:outline-none focus-visible:bg-indigo-500/10"
            >
              <div class="flex items-center justify-between gap-2">
                <h3 class="truncate text-sm font-semibold text-stone-900 dark:text-stone-50">{{ thread.name }}</h3>
                <span class="shrink-0 text-xs text-stone-500 dark:text-stone-400">{{ thread.human_timestamp }}</span>
              </div>
              <p class="truncate text-xs text-stone-500 dark:text-stone-400">
                {{ thread.participants.map((p) => p.name).join(', ') }}
              </p>
              <p v-if="thread.description" class="truncate text-xs italic text-stone-500 dark:text-stone-400">
                {{ thread.description }}
              </p>
            </Link>
          </li>
        </ul>

        <GlassEmptyState
          v-else
          icon="ChatBubbleLeftRightIcon"
          title="No conversations yet"
          description="Start a new chat or invite someone to message you."
          class="m-3"
        />
      </aside>

      <section class="flex min-h-0 min-w-0 flex-1 items-center justify-center p-6">
        <GlassEmptyState
          icon="ChatBubbleBottomCenterTextIcon"
          title="Open a thread"
          description="Pick a conversation on the left to start reading."
        />
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';

const page = usePage();
const threads = computed(() => page.props.threads?.data ?? []);
const totalLabel = computed(() => {
  const total = page.props.threads?.total ?? threads.value.length;
  return `${total} ${total === 1 ? 'thread' : 'threads'}`;
});
</script>
