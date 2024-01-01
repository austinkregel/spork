<template>
    <div class="h-full border overflow-auto p-2 relative">
        <h2 class="text-2xl mb-4">Properties Panel</h2>
        <button class="absolute top-0 right-0 m-3" @click="$emit('clear')">
            <XMarkIcon class="h-6 w-6 text-stone-500" />
        </button>
        <div v-if="selectedComponent">
            <h3>{{ selectedComponent.type }}</h3>
            <div v-for="(value, propName) in selectedComponent.props" :key="propName">
                <label :for="propName" class="block text-sm font-medium text-stone-700">
                    {{ propName }}
                </label>
                <input
                    :id="propName"
                    :type="['on', 'off', 'true', 'false', true, false].includes(selectedComponent.props[propName]) ? 'checkbox' : 'text'"
                    v-model="selectedComponent.props[propName]"
                    :value="true"
                    class="mt-1 block py-2 px-3 border border-stone-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-slate-500 focus:border-slate-500 sm:text-sm"
                />
            </div>
        </div>
        <div v-else>
            <p>Select a component to view its properties.</p>
        </div>
    </div>
</template>

<script>
import { XMarkIcon } from '@heroicons/vue/24/solid';
export default {
    name: 'PropertiesPanel',
    components: {
        XMarkIcon,
    },
    props: {
        selectedComponent: {
            type: Object,
            required: false,
            default: () => null,
        },
    },
};
</script>
