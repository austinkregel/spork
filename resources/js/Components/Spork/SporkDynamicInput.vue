<template>
    <div class="w-full flex flex-col">
        <input
            :value="modelValue"
            @input="$emit('update:modelValue', {
                ...modelValue,
                name: $event.target.value
            })"
            class="bg-transparent w-full rounded-t-md text-xs leading-loose tracking-wide font-bold py-0 px-4" placeholder="text" type="text"
        />
        <label
            class="flex dark:bg-zinc-500 dark:placeholder-zinc-300 rounded-b-md h-10"
            v-if="type !== 'object'"
            :class="[type === 'checkbox' ? 'pl-4 pt-4': 'p-0']"
        >
            <input
                class="py-2 px-3 block shadow-sm sm:text-sm dark:bg-zinc-500 dark:placeholder-zinc-300 rounded-b-md"

                :class="[type !== 'checkbox' ? 'w-full': 'bg-zinc-50 border-zinc-50 dark:border-zinc-400 border p-1']"
                :value="modelValue"
                @input="$emit('update:modelValue', {
                    ...modelValue,
                    value: $event.target.value
                })"
                :type="type"
            />

        </label>
    </div>
</template>

<script>
export default {
    props: ['modelValue', 'type', 'autofocus'],
    emits: ['update:modelValue'],
}
</script>

