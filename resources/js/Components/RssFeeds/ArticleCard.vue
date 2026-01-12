<script setup>
const props = defineProps({
  article: {
    type: Object,
    required: true,
  },
});

const date = (d) => dayjs(d).format('YYYY-MM-DD HH:mm:ss');
</script>

<template>
  <div class="p-4 hover:bg-stone-50 dark:hover:bg-stone-800/40 transition">
    <div class="min-w-0">
      <a
        target="_blank"
        :href="article.url"
        class="text-base font-semibold text-stone-900 dark:text-stone-100 hover:underline break-words"
      >
        {{ article.headline }}
      </a>

      <div class="mt-2 text-sm text-stone-600 dark:text-stone-300 line-clamp-3">
        <div v-html="article.content"></div>
      </div>

      <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
        <span
          v-for="tag in article.author?.tags ?? []"
          :key="tag.id ?? tag.name?.en ?? tag.name"
          class="py-1 px-2 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200"
        >
          {{ tag?.name?.en ?? tag?.name }}
        </span>
        <span class="py-1 px-2 rounded-full bg-stone-900/90 dark:bg-stone-700 text-white">
          {{ date(article.created_at) }}
        </span>
      </div>
    </div>
  </div>
</template>

