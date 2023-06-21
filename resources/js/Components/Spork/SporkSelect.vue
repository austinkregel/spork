<!-- This example requires Tailwind CSS v2.0+ -->
<template>
    <select
        @input="$emit('update:modelValue', $event.target.value)"
        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-zinc-300 dark:border-zinc-500 dark:bg-zinc-500 dark:placeholder-zinc-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
    >
        <slot></slot>
    </select>
</template>

<script>
export default {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    methods: {
        focus() {
            this.$refs.input.focus()
        }
    }
}
</script>

