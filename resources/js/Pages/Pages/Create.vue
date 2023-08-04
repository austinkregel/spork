<template>
    <div id="app" class="h-screen grid grid-cols-4 dark:text-white">
        <!-- Components List -->
        <div class="col-span-1 bg-blue-200 dark:bg-blue-700 p-4 overflow-y-auto h-full">
            <h2 class="text-2xl mb-4 font-bold">Component Library</h2>
            <VueDraggableNext
                v-model="components"
                :options="{ group: { name: 'components', pull: 'clone', put: false }, sort: false }"
                @start="dragStart"
                @end="dragEnd"
                :drag="false"
            >
                <div
                    v-for="(element, index) in components"
                     :key="index+'.components'"
                    class="flex flex-wrap items-center gap-4 w-full"
                >
                    <div
                        class="w-8"
                        v-html="element.svg"
                    ></div>
                    <div
                        class="flex-grow"
                        v-html="element.name"
                    ></div>
                </div>
            </VueDraggableNext>
        </div>

        <!-- Canvas Area -->
        <div class="p-4 overflow-y-auto h-full dark:bg-slate-900"
            :class="[selectedComponent ? 'col-span-2' : 'col-span-3']"
        >
            <h2 class="text-2xl mb-4 font-bold">Website Canvas</h2>
            <DraggableComponent
                id="canvas"
                :components="canvasComponents"
                @update="updateComponents($event)"
                @select="({ component }) => selectedComponent = component"
            />
        </div>

        <!-- Properties Panel -->
        <PropertiesPanel
            v-if="selectedComponent"
            :selected-component="selectedComponent"
            @update="updateComponentProps"
            @clear="selectedComponent = null"
        />
    </div>
</template>

<script>
import { VueDraggableNext } from 'vue-draggable-next';
import DraggableComponent from '../../Components/DraggableComponent.vue';
import PropertiesPanel from '../../Components/PropertiesPanel.vue';
export default {
    name: 'App',
    components: {
        VueDraggableNext,
        DraggableComponent,
        PropertiesPanel,

     },
    data() {
        return {
            components: this.$store.getters.components,
            canvasComponents: [
                {
                    id: "392902",
                    name: "Grid",
                    props: {
                        columns: 1,
                    },
                    children: [
                        {
                            id: "0239032",
                            name: 'BuilderButton',
                            props: {
                                text: 'Click Me',
                                small: true,
                            },
                        }
                    ]
                }
            ],
            selectedComponent: null,
            draggedComponent: null,
            console: console,
        };
    },
    methods: {
        dragStart(evt) {
            this.draggedComponent = this.components[evt.oldIndex];
        },
        dragEnd(evt) {
            if (evt.originalEvent?.target?.id === 'canvas') {
                this.updateComponents({
                    action: 'add',
                    index: evt.newIndex,
                    item: this.draggedComponent,
                })
            }
            this.draggedComponent = null;
        },
        updateComponents({ action, index, item }) {
            if (action === 'add') {
                this.canvasComponents.push(item);
            } else if (action === 'remove') {
                this.canvasComponents.splice(index, 1);
            }
        },
        updateComponentProps({ propName, value }) {
            this.selectedComponent.props[propName] = value;
        },
    },
};
</script>
