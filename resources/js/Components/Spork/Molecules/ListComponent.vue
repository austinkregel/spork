<template>
    <component :is="WrapperComponent" role="list" class="divide-y divide-stone-800">
        <slot v-for="item in items" :key="item" :item="item" class="flex justify-between gap-x-6 py-5"></slot>
    </component>
</template>

<script setup>
defineProps({
    items: Array,
    WrapperComponent: {
        type: String,
        default: 'div',
    },
});
</script>
