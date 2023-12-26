<template>
    <button @click="(e) => $emit('click', e)" :class="[active ? 'bg-stone-100 text-stone-900 dark:bg-stone-700 dark:text-stone-50' : 'text-stone-700 dark:text-stone-50', 'group flex items-center px-4 py-2 text-sm w-full']">
        <Component :is="icon" class="mr-3 h-5 w-5 text-stone-400 dark:text-stone-50 group-hover:text-stone-500" aria-hidden="true" />
        {{text}}
    </button>
</template>

<script>
export default {
    props: ['active', 'icon', 'text'],
    emits: ['click']
}
</script>
