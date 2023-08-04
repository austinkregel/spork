<template>
    <VueDraggableNext
        class="canvas p-4 border border-slate-600"
        :list="components"
        :options="{ sort: true }"
        :group="{ name: 'canvas' }"
        @start="setDraggingComponent"
        @end="cloneComponent"
        drag-class="ghost-component"
    >
        <div
            v-for="(component, index) in components"
            :key="'component-'+component?.name"
            @click="selectComponent(component, index)"
            class="p-4 border border-green-100"
            v-bind="component.props"
            :class="{ 'selected-component': component.selected }"
        >
            <component :is="component.name" v-bind="component.props"></component>
        </div>
    </VueDraggableNext>
</template>

<script>
import { VueDraggableNext } from 'vue-draggable-next';
import { ref, watchEffect } from 'vue';
export default {
    name: 'DraggableComponent',
    props: {
        components: {
            type: Array,
            required: true,
        },
    },
    components: {
        VueDraggableNext,
    },
    setup(props, { emit }) {
        const components = ref(props.components);
        watchEffect(() => {
            components.value = props.components;
        });

        const draggingComponent = ref(null);
        return {
            draggingComponent,
            components,
            setDraggingComponent(evt) {
                draggingComponent.value = components.value[evt.oldIndex];
            },
            cloneComponent(evt) {
                console.log('clone draggable', evt, components.value);
                draggingComponent.value = null;
                if (evt.to === evt.from) {
                    return;
                }

                console.log('clone draggable', evt, components.value)
                components.value[evt.newIndex] = {
                    ...components.value[evt.newIndex],
                    id: Date.now(),
                    props: { ...(components.value[evt.newIndex]?.props ?? {}) },
                    children: components.value[evt.newIndex]?.children
                        ? JSON.parse(JSON.stringify(components.value[evt.newIndex].children))
                        : [],
                };
                emit('update', components.value, evt.newIndex);
            },
            updateChildren(newChildren, index) {
                console.log('update children', newChildren, index, components.value)

                components.value[index].children = newChildren;
                emit('update', components.value, index);
            },
            selectComponent(component, index) {
                emit('select', {
                    component, index
                })
            },
        };
    },
};
</script>

<style scoped>
.selected-component {
    outline: 2px solid blue;
}
.ghost-component {
    opacity: 0.5;
    background: #dcdcdc;
}

</style>
