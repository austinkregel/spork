<template>
  <aside
    v-if="items.length"
    class="hidden xl:flex w-56 shrink-0 flex-col gap-2 p-3 backdrop-blur-glass border-r border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-[var(--color-glass-surface-light)] dark:bg-[var(--color-glass-surface-dark)]"
    aria-label="Pillar sub-navigation"
  >
    <div class="px-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
      In this pillar
    </div>

    <template v-for="(item, idx) in items" :key="idx">
      <div v-if="item.children && item.children.length" class="space-y-1">
        <Link
          :href="item.href"
          :class="[groupHeaderClass, isActive(item.href) ? activeGroupHeaderClass : '']"
          :aria-current="isActive(item.href) ? 'page' : undefined"
        >
          <DynamicIcon :icon-name="item.icon" class="h-4 w-4 text-stone-500" aria-hidden="true" />
          <span>{{ item.label }}</span>
        </Link>
        <Link
          v-for="(child, cidx) in item.children"
          :key="cidx"
          :href="child.href"
          :class="[childItemClass, isActive(child.href) ? activeChildItemClass : '']"
          :aria-current="isActive(child.href) ? 'page' : undefined"
        >
          <DynamicIcon :icon-name="child.icon" class="h-4 w-4 text-stone-500" aria-hidden="true" />
          <span>{{ child.label }}</span>
        </Link>
      </div>
      <Link
        v-else
        :href="item.href"
        :class="[topItemClass, isActive(item.href) ? activeTopItemClass : '']"
        :aria-current="isActive(item.href) ? 'page' : undefined"
      >
        <DynamicIcon :icon-name="item.icon" class="h-5 w-5 text-stone-500" aria-hidden="true" />
        <span>{{ item.label }}</span>
      </Link>
    </template>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import DynamicIcon from '@/Components/DynamicIcon.vue';

defineProps({
  items: { type: Array, default: () => [] },
});

const page = usePage();

const currentPath = computed(() => {
  try {
    return new URL(page.url, 'http://x').pathname;
  } catch (e) {
    return page.url || '';
  }
});

function hrefPath(href) {
  if (!href) return '';
  try {
    return new URL(href, 'http://x').pathname;
  } catch (e) {
    return href;
  }
}

function isActive(href) {
  return hrefPath(href) === currentPath.value;
}

const focusRing =
  'focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950';

const transition = 'transition-colors motion-reduce:transition-none';

const groupHeaderClass = `flex items-center gap-2 rounded-md px-2 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-200/70 dark:text-stone-300 dark:hover:bg-stone-700/60 ${transition} ${focusRing}`;

const childItemClass = `flex items-center gap-2 rounded-md pl-4 pr-2 py-1.5 text-sm text-stone-800 hover:bg-stone-200/70 dark:text-stone-100 dark:hover:bg-stone-700/60 ${transition} ${focusRing}`;

const topItemClass = `flex items-center gap-2 rounded-md px-2 py-2 text-sm text-stone-800 hover:bg-stone-200/70 dark:text-stone-100 dark:hover:bg-stone-700/60 ${transition} ${focusRing}`;

const activeGroupHeaderClass =
  'bg-indigo-500/15 text-indigo-700 ring-1 ring-indigo-500/30 dark:bg-indigo-500/20 dark:text-indigo-100 dark:ring-indigo-400/40';

const activeChildItemClass =
  'bg-indigo-500/15 text-indigo-700 ring-1 ring-indigo-500/30 dark:bg-indigo-500/20 dark:text-indigo-100 dark:ring-indigo-400/40';

const activeTopItemClass =
  'bg-indigo-500/15 text-indigo-700 ring-1 ring-indigo-500/30 dark:bg-indigo-500/20 dark:text-indigo-100 dark:ring-indigo-400/40';
</script>
