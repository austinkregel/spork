<template>
    <div class="w-full flex flex-col">
        <label class="flex flex-col">
            <span class="">{{ label }}</span>
            <input
                :value="modelValue"
                @input="$emit('update:modelValue', {
                ...modelValue,
                name: $event.target.value
            })"
                class="bg-transparent w-full rounded-t-md text-xs leading-loose tracking-wide font-bold py-0 px-4" placeholder="text" type="text"
            />

        </label>
        <label
            class="flex dark:bg-stone-500 dark:placeholder-stone-300 rounded-b-md h-10"
            v-if="type !== 'object'"
            :class="[type === 'checkbox' ? 'pl-4 pt-4': 'p-0']"
        >
            <input
                class="py-2 px-3 block shadow-sm sm:text-sm dark:bg-stone-500 dark:placeholder-stone-300 rounded-b-md"

                :class="[type !== 'checkbox' ? 'w-full': 'bg-stone-50 border-stone-50 dark:border-stone-400 border p-1']"
                :value="modelValue"
                @input="$emit('update:modelValue', {
                    ...modelValue,
                    value: $event.target.value
                })"
                :type="type"
            />
            {{ label }}
        </label>
    </div>
</template>

<script>
export default {
    props: {
        modelValue: {
            type: Object,
            default: () => ({}),
        },
        type: {
            type: String,
            default: 'text',
        },
        autofocus: {
            type: Boolean,
            default: false,
        },
        label: {
            type: String,
            default: 'Text Field Label',
        },
    },
    emits: ['update:modelValue'],
}
</script>

