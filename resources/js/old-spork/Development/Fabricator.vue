<template>
    <div class="w-full h-full border border-stone-100 flex flex-wrap text-white">
        <KeepAlive>
            <div  class="w-full h-full border border-stone-100 flex flex-wrap text-white">
                <div class="w-3/4 border border-stone-200">
                    <draggable
                        v-model="contents"
                        group="components"
                        class="p-8 border border-red-400 flex flex-wrap"
                        :group="{ name: 'components', pull: 'move', put: true }"
                        item-key="id"
                    >
                        <template #item="{element}">
                            <component
                                v-if="element.component"
                                :is="element.component"
                                :class="element.width"
                            ></component>
                            <div v-else>{{ element}}</div>
                        </template>
                    </draggable>
                </div>
                <div class="w-1/4 border border-stone-300">

                    <draggable
                        :list="components"
                        class="p-8 border border-red-400"
                        :group="{ name: 'components', pull: 'clone', put: false }"
                        item-key="id"
                        :sort="true"

                    >
                        <template #item="{ element }">
                            <div class="border-t border-stone-400 dark:border-stone-500">
                                <div class="flex flex-col my-4">
                                    <div class="font-bold">{{ element.name }}</div>
                                    <div>{{ element.description }}</div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>
        </KeepAlive>
    </div>
</template>

<script>
/**
 * Fabricator will be a plugin focused on dynamic page creation.
 *   It'll generate a vue component to be used for new routes.
 *   It'll have a drag and drop GUI for building out the component, filling static information, and setting the route path.
 *      Meaning, I want it to have a set of Vue components it can have a preview of, and then I can drag and drop them into the page.
 *   I want to be able to set properties on the component, predefined data portions, computed values etc, ...
 */
import draggable from 'vuedraggable'

export default {
    components: {
        draggable,
    },
    data() {
        return {
            draggingComponent: false,
            draggingContent: false,
            components: [
                {
                    id: 1,
                    name: 'Calendar',
                    description: "A calendar for selecting inputs",
                    type: 'calendar',
                    settings: {},
                },
                {
                    id: 2,
                    name: 'Input',
                    description: "An input",
                    type: 'text',
                    settings: {},
                },
                {
                    id: 3,
                    name: 'Input',
                    description: "An input",
                    type: 'textarea',
                    settings: {},
                },
                {
                    id: 4,
                    name: 'Number Input',
                    description: "A number input",
                    type: 'number',
                    settings: {},
                }
            ],
            contents: [],
        }
    }
}
</script>
