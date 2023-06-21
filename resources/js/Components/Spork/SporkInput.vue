<template>
    <input
        class="py-2 px-4 border focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-zinc-300 dark:border-zinc-700 dark:bg-zinc-700 dark:placeholder-zinc-300 rounded-md"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
        :type="type"
    />

</template>

<script>
export default {
    props: [
        'modelValue',
        'type'
    ],
    emits: ['update:modelValue'],
    methods: {
        focus() {
            this.$refs.input.focus()
        }
    }
}
</script>

