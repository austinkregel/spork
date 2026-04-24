<template>
  <AppLayout :title="thread?.name ?? 'Conversation'">
    <div class="flex h-[calc(100vh-4rem)] min-h-0 w-full">
      <aside
        class="hidden w-full max-w-sm shrink-0 flex-col border-r border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass md:flex xl:w-96"
        aria-label="Threads"
      >
        <header class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-3">
          <h1 class="text-base font-semibold text-stone-900 dark:text-stone-50">Conversations</h1>
        </header>
        <ul
          v-if="threads.length"
          class="min-h-0 flex-1 divide-y divide-[var(--color-glass-border-light)] overflow-y-auto dark:divide-[var(--color-glass-border-dark)]"
        >
          <li v-for="t in threads" :key="t.id">
            <Link
              :href="route('communication.chat.show', t.id)"
              :class="[
                'flex flex-col gap-1 px-4 py-3 transition-colors motion-reduce:transition-none focus:outline-none focus-visible:bg-indigo-500/10',
                thread?.id === t.id
                  ? 'bg-indigo-500/10'
                  : 'hover:bg-stone-100/60 dark:hover:bg-stone-800/40',
              ]"
            >
              <div class="flex items-center justify-between gap-2">
                <h3 class="truncate text-sm font-semibold text-stone-900 dark:text-stone-50">{{ t.name }}</h3>
                <span class="shrink-0 text-xs text-stone-500 dark:text-stone-400">{{ t.human_timestamp }}</span>
              </div>
              <p class="truncate text-xs text-stone-500 dark:text-stone-400">
                {{ t.participants.map((p) => p.name).join(', ') }}
              </p>
            </Link>
          </li>
        </ul>
        <GlassEmptyState
          v-else
          icon="ChatBubbleLeftRightIcon"
          title="No conversations"
          description="Threads will appear here once they're started."
          class="m-3"
        />
      </aside>

      <section class="flex min-h-0 min-w-0 flex-1 flex-col">
        <header
          class="flex shrink-0 items-center justify-between gap-4 border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass px-4 py-3 sm:px-6"
        >
          <div class="flex min-w-0 items-center gap-3">
            <div class="flex -space-x-2">
              <img
                v-for="(participant, i) in participantStack"
                :key="participant.id ?? i"
                :src="avatarFor(participant)"
                alt=""
                class="inline-block h-8 w-8 rounded-full ring-2 ring-white dark:ring-stone-800"
              />
            </div>
            <div class="min-w-0">
              <h2 class="truncate text-sm font-semibold text-stone-900 dark:text-stone-50">
                {{ thread?.participants.map((p) => p.name).join(', ') }}
              </h2>
              <p v-if="thread?.topic" class="truncate text-xs text-stone-500 dark:text-stone-400">
                {{ thread.topic }}
              </p>
            </div>
          </div>
        </header>

        <article class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-6">
          <ol v-if="thread?.messages?.length" class="flex flex-col gap-3">
            <li
              v-for="message in thread.messages"
              :key="message.id ?? message.uuid ?? message.originated_at"
              class="flex items-end gap-3"
              :class="message.is_user ? 'justify-end' : 'justify-start'"
            >
              <img
                v-if="!message.is_user"
                :src="avatarFor(message.from_person)"
                alt=""
                class="h-8 w-8 shrink-0 rounded-full"
              />
              <div
                :class="[
                  'max-w-xl space-y-1 rounded-2xl px-3 py-2 shadow-sm',
                  message.is_user
                    ? 'bg-indigo-500 text-white'
                    : 'bg-[var(--color-glass-surface-strong-light)] text-stone-900 backdrop-blur-glass dark:bg-[var(--color-glass-surface-strong-dark)] dark:text-stone-100',
                ]"
              >
                <div :class="['text-xs', message.is_user ? 'text-indigo-100' : 'text-stone-500 dark:text-stone-400']">
                  <span class="font-medium">{{ message?.from_person?.name }}</span>
                  <span class="ml-1">· {{ formatDate(message.originated_at) }}</span>
                </div>
                <img
                  v-if="message.thumbnail_url || message.message?.startsWith('https://tenor.com')"
                  :src="message.thumbnail_url ?? message.message"
                  :alt="message.message"
                  class="w-64 rounded-md"
                />
                <Markdown
                  v-if="message.message"
                  :source="message.message"
                  :class="['prose prose-sm max-w-none', message.is_user ? 'prose-invert' : 'dark:prose-invert']"
                />
              </div>
              <img
                v-if="message.is_user"
                :src="avatarFor(message.from_person)"
                alt=""
                class="h-8 w-8 shrink-0 rounded-full"
              />
            </li>
          </ol>
          <GlassEmptyState
            v-else
            icon="ChatBubbleBottomCenterTextIcon"
            title="No messages yet"
            description="Be the first to say something."
          />
        </article>

        <footer
          class="shrink-0 border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass p-3"
        >
          <form class="flex flex-col gap-2" @submit.prevent="reply">
            <label for="reply" class="sr-only">Reply</label>
            <textarea
              id="reply"
              v-model="replyText"
              rows="3"
              placeholder="Type your reply…"
              class="block w-full rounded-md border border-stone-300 bg-white/70 px-3 py-2 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-stone-600 dark:bg-stone-800/70 dark:text-stone-100 dark:placeholder:text-stone-500"
            />
            <div class="flex items-center justify-between">
              <GlassIconButton variant="ghost" size="sm" type="button" label="Attach file">
                <PaperClipIcon class="h-4 w-4" aria-hidden="true" />
              </GlassIconButton>
              <GlassButton type="submit" :disabled="!replyText.trim()" :icon-right="PaperAirplaneIcon">
                Reply
              </GlassButton>
            </div>
          </form>
        </footer>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import relativeTime from 'dayjs/plugin/relativeTime';
import { PaperAirplaneIcon, PaperClipIcon } from '@heroicons/vue/24/outline';

import AppLayout from '@/Layouts/AppLayout.vue';
import Markdown from '@/Components/Spork/Molecules/Markdown.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassIconButton from '@/Components/Glass/GlassIconButton.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';

dayjs.extend(utc);
dayjs.extend(relativeTime);

const page = usePage();

const thread = computed(() => page.props.thread);
const threads = computed(() => page.props.threads?.data ?? []);

const replyText = ref('');

const participantStack = computed(() => thread.value?.participants?.slice(0, 5) ?? []);

function avatarFor(person) {
  if (!person) return '';
  return person.photo_url ?? `/storage/${person.id}.png`;
}

function reply() {
  if (!replyText.value.trim()) return;
  // Reply submission wiring is handled by the existing chat backend; this is a stub
  // until that flow is finished. We still clear the input for UX feedback.
  replyText.value = '';
}

function normalizeTimestamp(value) {
  if (value === null || value === undefined) {
    return dayjs.invalid();
  }
  if (typeof value === 'number') {
    const seconds = value > 1e12 ? value / 1000 : value;
    return dayjs.unix(seconds).utc();
  }
  const numeric = Number(value);
  if (!Number.isNaN(numeric)) {
    const seconds = numeric > 1e12 ? numeric / 1000 : numeric;
    return dayjs.unix(seconds).utc();
  }
  return dayjs.utc(value);
}

function formatDate(value) {
  const instance = normalizeTimestamp(value);
  if (!instance.isValid()) return '';
  return instance.fromNow();
}
</script>
