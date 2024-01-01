<template>
    <div :class="`grid grid-cols-${columns} p-1`">
        <slot />
    </div>
</template>

<script>
import DraggableComponent from "@/Components/DraggableComponent.vue";
export default {
    name: "Grid",
    props: {
        columns: {
            type: Number,
            default: 12,
        },
    },
    components: {
        DraggableComponent,
    },
    methods: {
    },
};
</script>

<style scoped>
.col-span-1 {
    border: 1px solid #ccc;
    padding: 1em;
}
</style>
