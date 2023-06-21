<template>
    <button @click="(e) => $emit('click', e)" :class="[active ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-700 dark:text-zinc-50' : 'text-zinc-700 dark:text-zinc-50', 'group flex items-center px-4 py-2 text-sm w-full']">
        <Component :is="icon" class="mr-3 h-5 w-5 text-zinc-400 dark:text-zinc-50 group-hover:text-zinc-500" aria-hidden="true" />
        {{text}}
    </button>
</template>

<script>
export default {
    props: ['active', 'icon', 'text'],
    emits: ['click']
}
</script>
