<script>
export default {
    props: {
        header: {
            default: "Card Header"
        },
        footer: {
            default: "Card Footer"
        },
        body: {
            default: "Card Text"
        },
        shadow: {
            default: () => false,
        },
        darkHeader: {
            default: () => false,
        },
        darkFooter: {
            default: () => false,
        },
    },
    data() {
        return {

        };
    }
}
</script>

<template>
    <div class="flex flex-col gap-2 border border-slate-200 dark:border-slate-800 rounded-lg"
        :class="[
            shadow ? 'shadow' : ''
        ]"
    >
        <div class="py-2 px-4" :class="[
            darkHeader ? 'dark:bg-slate-800  dark:text-slate-200 bg-slate-100 text-slate-800' : '',
        ]">{{ header }}</div>
        <div class="p-4">{{ body }}</div>
        <div class="py-2 px-4" :class="[
            darkFooter ? 'dark:bg-slate-800 dark:text-slate-200 bg-slate-100 text-slate-800' : '',
        ]">{{ footer }}</div>
    </div>
</template>
