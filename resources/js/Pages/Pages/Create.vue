<template>
    <div id="app" class="h-screen grid grid-cols-4 gap-4 p-4">
        <!-- Components List -->
        <div class="col-span-1 bg-blue-200 rounded-lg p-4 overflow-y-auto h-full">
            <h2 class="text-2xl mb-4 font-bold">Component Library</h2>
            <VueDraggableNext
                v-model="components"
                :options="{ group: { name: 'components', pull: 'clone', put: false } }"
                @start="dragStart"
                @end="dragEnd"
                :drag="false"
            >
                <div
                    v-for="(element, index) in components"
                     :key="index"
                    class="bg-white p-2 rounded my-1"
                    v-html="element.svg"
                ></div>
            </VueDraggableNext>
        </div>

        <!-- Canvas Area -->
        <div class="col-span-2 bg-green-200 rounded-lg p-4 overflow-y-auto h-full">
            <h2 class="text-2xl mb-4 font-bold">Website Canvas</h2>
            <VueDraggableNext
                v-model="canvasComponents"
                :options="{ group: { name: 'canvas' } }"
                @add="cloneComponent"
                class="py-4 bg-green-800"
            >
                <component
                    v-for="component in canvasComponents"
                    @click="selectedComponent = component"
                    :key="component.id"
                    :is="component.name"
                    class="my-4"
                    v-bind="component.props"
                ></component>
            </VueDraggableNext>


        </div>

        <!-- Properties Panel -->
        <div class="h-full w-64 border overflow-auto p-2">
            <h2 class="text-2xl mb-4">Properties Panel</h2>
            <div v-if="selectedComponent">
                <h3>{{ selectedComponent.type }}</h3>
                <div v-for="(value, propName) in selectedComponent.props" :key="propName">
                    <label :for="propName" class="block text-sm font-medium text-gray-700">
                        {{ propName }}
                    </label>
                    <input
                        :id="propName"
                        v-model="selectedComponent.props[propName]"
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    />
                </div>
            </div>
            <div v-else>
                <p>Select a component to view its properties.</p>
            </div>
        </div>

    </div>
</template>

<script>
import { VueDraggableNext } from 'vue-draggable-next';
import Hero from '../../Components/Hero.vue';

export default {
    name: 'App',
    components: {
        VueDraggableNext,
        Hero,
    },
    data() {
        return {
            components: [
                {
                    id: 1,
                    name: 'Hero',
                    svg: `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50">
        <path d="M10 25 Q 15 5, 20 25 Q 25 45, 30 25 Q 35 5, 40 25" stroke="black" fill="transparent"/>
      </svg>
    `,
                    props: {
                        background: '',
                        title: 'Hello, Vue!',
                        description: 'This is a hero component',
                        ctaText: 'Learn more',
                        ctaLink: '#',
                    },
                },
            ]
            ,
            canvasComponents: [],
            selectedComponent: null,
            draggedComponent: null,
        };
    },
    methods: {
        selectComponent(component) {
            this.selectedComponent = component;
        },
        dragStart(evt) {
            this.draggedComponent = this.components[evt.oldIndex];
            console.log(this.draggedComponent, evt);
        },
        dragEnd(evt) {
            this.cloneComponent(evt);
        },
        cloneComponent(evt) {
            console.log('logging');
            this.canvasComponents[evt.newIndex] = {
                ...this.draggedComponent,
                id: Date.now(),
                props: { ...this.draggedComponent.props }
            };

            console.log(this.canvasComponents, evt);
        },

    },
};
</script>
