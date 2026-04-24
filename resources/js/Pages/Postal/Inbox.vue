<template>
  <AppLayout title="Inbox">
    <div class="flex h-[calc(100vh-4rem)] min-h-0 w-full">
      <aside
        class="flex w-full max-w-sm shrink-0 flex-col border-r border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass xl:w-96"
        aria-label="Message list"
      >
        <header class="flex items-center justify-between border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-3">
          <div class="min-w-0">
            <h1 class="text-base font-semibold text-stone-900 dark:text-stone-50">Inbox</h1>
            <p class="text-xs text-stone-500 dark:text-stone-400">{{ totalLabel }}</p>
          </div>
          <div
            v-if="loading"
            class="inline-flex items-center gap-1 rounded-full bg-indigo-500/10 px-2 py-0.5 text-xs font-medium text-indigo-600 dark:text-indigo-300"
            role="status"
          >
            <ArrowPathIcon class="h-3 w-3 animate-spin motion-reduce:animate-none" aria-hidden="true" />
            Loading
          </div>
        </header>

        <ul
          v-if="messages.length"
          class="min-h-0 flex-1 divide-y divide-[var(--color-glass-border-light)] overflow-y-auto dark:divide-[var(--color-glass-border-dark)]"
        >
          <li v-for="item in messages" :key="item.id">
            <button
              type="button"
              :class="[
                'flex w-full flex-col gap-1 px-4 py-3 text-left transition-colors motion-reduce:transition-none focus:outline-none focus-visible:bg-indigo-500/10',
                openMail && item.id === openMail.id
                  ? 'bg-indigo-500/15 dark:bg-indigo-500/15'
                  : item.seen
                    ? 'hover:bg-stone-100/60 dark:hover:bg-stone-800/40'
                    : 'bg-stone-100/40 hover:bg-stone-200/50 dark:bg-stone-800/30 dark:hover:bg-stone-700/40',
              ]"
              @click="changeIframe(item)"
              @contextmenu.prevent="openContextMenu($event, item)"
            >
              <div class="flex items-center justify-between gap-2">
                <span
                  :class="[
                    'truncate text-sm',
                    item.seen ? 'font-medium text-stone-700 dark:text-stone-200' : 'font-semibold text-stone-900 dark:text-stone-50',
                  ]"
                >
                  {{ fromLabel(item) }}
                </span>
                <span class="shrink-0 text-xs text-stone-500 dark:text-stone-400">{{ item.human_date }}</span>
              </div>
              <p
                :class="[
                  'truncate text-sm',
                  item.seen ? 'text-stone-600 dark:text-stone-300' : 'font-medium text-stone-900 dark:text-stone-50',
                ]"
              >
                {{ item.subject || '(no subject)' }}
              </p>
              <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ item.from_email }}</p>
              <div class="mt-1 flex items-center justify-between">
                <div class="flex items-center gap-1.5 text-stone-500 dark:text-stone-400">
                  <EyeSlashIcon v-if="!item.seen" class="h-4 w-4" aria-label="Unread" />
                  <FireIcon v-if="item.spam > 1" class="h-4 w-4 text-amber-500" aria-label="Possible spam" />
                  <ArrowUturnLeftIcon v-if="item.answered" class="h-4 w-4" aria-label="Replied" />
                  <TrashIcon v-if="item.deleted" class="h-4 w-4" aria-label="Deleted" />
                  <PencilSquareIcon v-if="item.draft" class="h-4 w-4" aria-label="Draft" />
                </div>
                <div v-if="item.tags?.length" class="flex flex-wrap items-center gap-1">
                  <GlassPill
                    v-for="tag in item.tags.slice(0, 2)"
                    :key="tag.id ?? tag.name?.en ?? tag"
                    size="sm"
                    tone="indigo"
                  >
                    {{ tagLabel(tag) }}
                  </GlassPill>
                  <span
                    v-if="item.tags.length > 2"
                    class="text-xs text-stone-500 dark:text-stone-400"
                  >+{{ item.tags.length - 2 }}</span>
                </div>
              </div>
            </button>
          </li>
        </ul>

        <GlassEmptyState
          v-else
          icon="InboxIcon"
          title="Inbox is empty"
          description="No messages match the current view."
          class="m-3"
        />

        <footer class="flex items-center justify-between gap-2 border-t border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] px-4 py-2">
          <GlassButton
            :href="page.props.messages?.prev_page_url ?? null"
            :disabled="!page.props.messages?.prev_page_url"
            variant="outline"
            size="sm"
          >
            Previous
          </GlassButton>
          <GlassButton
            :href="page.props.messages?.next_page_url ?? null"
            :disabled="!page.props.messages?.next_page_url"
            variant="outline"
            size="sm"
          >
            Next
          </GlassButton>
        </footer>
      </aside>

      <section class="flex min-h-0 min-w-0 flex-1 flex-col">
        <template v-if="openMail">
          <header
            class="flex items-start gap-3 border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)] backdrop-blur-glass px-4 py-3 sm:px-6"
          >
            <GlassIconButton
              variant="ghost"
              size="sm"
              :href="route('communication.postal.index')"
              label="Back to inbox"
              class="lg:hidden"
            >
              <ArrowUturnLeftIcon class="h-4 w-4" aria-hidden="true" />
            </GlassIconButton>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-50">
                  {{ openMail.subject || '(no subject)' }}
                </h2>
                <span class="shrink-0 text-xs text-stone-500 dark:text-stone-400">{{ openMail.human_date }}</span>
              </div>
              <dl class="mt-2 grid gap-1 text-xs text-stone-600 dark:text-stone-300 sm:grid-cols-[auto_1fr] sm:gap-x-3">
                <dt class="font-medium uppercase tracking-wide">From</dt>
                <dd class="truncate">
                  {{ peopleLabel(openMail.from) }}
                  <span class="ml-1 rounded bg-stone-100 px-1.5 py-0.5 font-mono text-[10px] text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                    {{ openMail.from_email }}
                  </span>
                </dd>
                <dt class="font-medium uppercase tracking-wide">To</dt>
                <dd class="truncate">
                  {{ peopleLabel(openMail.to) }}
                  <span class="ml-1 rounded bg-stone-100 px-1.5 py-0.5 font-mono text-[10px] text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                    {{ openMail.to_email }}
                  </span>
                </dd>
                <template v-if="openMail['reply-to']">
                  <dt class="font-medium uppercase tracking-wide">Reply-to</dt>
                  <dd class="truncate">{{ openMail['reply-to'].name }} {{ openMail['reply-to'].email }}</dd>
                </template>
              </dl>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <GlassPill v-if="openMail.spam > 1" :tone="spamTone" size="sm" dot>{{ spamLabel }}</GlassPill>
                <GlassPill v-if="openMail.answered" tone="info" size="sm">Replied</GlassPill>
                <GlassPill v-if="openMail.deleted" tone="danger" size="sm">Deleted</GlassPill>
                <GlassPill v-if="openMail.draft" tone="warning" size="sm">Draft</GlassPill>
                <GlassPill v-if="!openMail.seen" tone="indigo" size="sm">Unread</GlassPill>
              </div>
            </div>
          </header>
          <iframe
            :src="route('communication.postal.show', [openMail?.id])"
            class="min-h-0 flex-1 w-full border-0 bg-white dark:bg-stone-900"
            title="Message preview"
          />
        </template>
        <GlassEmptyState
          v-else
          icon="EnvelopeOpenIcon"
          title="Select a message"
          description="Pick something from the inbox on the left to read it here."
          class="m-6 self-center"
        />
      </section>
    </div>

    <div v-show="openContext && openForMail" class="fixed inset-0 z-40">
      <button
        type="button"
        class="absolute inset-0 bg-stone-900/20"
        aria-label="Close context menu"
        @click="closeContextMenu"
      />
      <div
        id="contextRef"
        :style="styleForContext"
        class="absolute z-50 w-56 overflow-hidden rounded-md border border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-strong-light)] dark:bg-[var(--color-glass-surface-strong-dark)] shadow-lg backdrop-blur-glass focus:outline-none"
        role="menu"
      >
        <div class="flex flex-col divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="mark_as_read">Mark as read</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="mark_move_spam">Mark &amp; move to spam</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="mark_as_unread">Mark as unread</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="reply">Reply</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="reply_all">Reply all</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="forward">Forward</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="apply_tag">Apply tag</button>
          <button class="px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-500/10 dark:text-red-400" @click="delete_mail">Delete</button>
          <button class="px-3 py-1.5 text-left text-sm text-stone-700 hover:bg-stone-100/60 dark:text-stone-200 dark:hover:bg-stone-700/60" @click="open_in_new_tab">Open in new tab</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import {
  ArrowPathIcon,
  ArrowUturnLeftIcon,
  EyeSlashIcon,
  FireIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';

import AppLayout from '@/Layouts/AppLayout.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassIconButton from '@/Components/Glass/GlassIconButton.vue';
import GlassPill from '@/Components/Glass/GlassPill.vue';
import GlassEmptyState from '@/Components/Glass/GlassEmptyState.vue';

const page = usePage();

const openMail = ref(null);
const openForMail = ref(null);
const contextX = ref(0);
const contextY = ref(0);
const openContext = ref(false);
const styleForContext = ref('');
const loading = ref(true);

const messages = computed(() => page.props.messages?.data ?? []);
const totalLabel = computed(() => {
  const total = page.props.messages?.total ?? messages.value.length;
  return `${total} ${total === 1 ? 'message' : 'messages'}`;
});

onMounted(() => {
  loading.value = false;
});

function fromLabel(item) {
  if (!item?.from?.length) return item?.from_email ?? '(unknown sender)';
  return item.from.map((person) => person.name || person.email).join(', ');
}

function peopleLabel(people) {
  if (!people?.length) return '';
  return people.map((person) => person.name || person.email).join(', ');
}

function tagLabel(tag) {
  if (!tag) return '';
  if (typeof tag === 'string') return tag;
  return tag.name?.en ?? tag.name ?? tag.label ?? '';
}

function changeIframe(item) {
  openMail.value = item;
}

function closeContextMenu() {
  openForMail.value = null;
  openContext.value = false;
}

function open_in_new_tab() {
  if (!openForMail.value) return;
  window.open(`${window.location.href}/${openForMail.value.id}`, '_blank')?.focus();
  closeContextMenu();
}

function openContextMenu(event, item) {
  openForMail.value = item;
  openContext.value = true;
  contextX.value = event.clientX;
  contextY.value = event.clientY;

  setTimeout(() => {
    const docRef = document.getElementById('contextRef');
    if (!docRef) return;
    if (contextY.value + docRef.clientHeight < window.innerHeight) {
      styleForContext.value = `top:${contextY.value}px;left:${contextX.value}px;`;
    } else {
      styleForContext.value = `top:${contextY.value - docRef.clientHeight}px;left:${contextX.value}px;`;
    }
  }, 1);
}

const spamTone = computed(() => {
  if (!openMail.value) return 'neutral';
  if (openMail.value.spam >= 10) return 'danger';
  if (openMail.value.spam >= 5) return 'warning';
  return 'success';
});

const spamLabel = computed(() => {
  if (!openMail.value) return '';
  if (openMail.value.spam >= 10) return `Spam · score ${openMail.value.spam}`;
  if (openMail.value.spam >= 5) return `Suspicious · score ${openMail.value.spam}`;
  return `Score ${openMail.value.spam}`;
});

async function triggerMailLoading() {
  closeContextMenu();
  router.reload({ only: ['messages', 'unread_email_count'] });
  loading.value = false;
}

async function postMailAction(endpoint) {
  if (!openForMail.value) return;
  loading.value = true;
  await axios.post(endpoint, { id: openForMail.value.id });
  await triggerMailLoading();
}

const mark_as_read = () => postMailAction('/api/mail/mark-as-read');
const mark_move_spam = () => postMailAction('/api/mail/mark-as-spam');
const mark_as_unread = () => postMailAction('/api/mail/mark-as-unread');
const reply = () => triggerMailLoading();
const reply_all = () => triggerMailLoading();
const forward = () => triggerMailLoading();
const apply_tag = () => triggerMailLoading();
const delete_mail = () => triggerMailLoading();
</script>
