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
        items: {
            type: Array,
            default:  [],
        },
    },
    components: {
        DraggableComponent,
    },
    methods: {
        updateChildren(newChildren, index) {
            this.items[index] = newChildren;
            this.$emit('update', this.items);
        },
        selectComponent(component, index) {
            this.$emit('select', { component, index });
        },
    },
};
</script>

<style scoped>
.col-span-1 {
    border: 1px solid #ccc;
    padding: 1em;
}
</style>
