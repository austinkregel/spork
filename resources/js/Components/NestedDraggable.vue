<template>
    <draggable
        class="px-4 pt-4 flex flex-wrap"
        style="min-width:300px;"
        tag="ul"
        :list="items"
        :group="{ name: 'g1' }"
        item-key="name"
    >
        <template #item="{ element }">
            <li class="w-full p-2 flex flex-col">
                <div class="font-bold flex justify-between p-2 border border-stone-500" style="width:400px;">
                    <div>{{element.name}}</div>
                    <button @click="open = !open"  v-if="open">&gt; <span v-if="open" class="underline">Close</span></button>
                    <button @click="open = !open"  v-if="!open">&lt; <span v-if="!open" class="underline">Open</span></button>
                </div>
                <div class="flex gap-2 flex-wrap p-4 w-full" v-if="open">
                    <label for="">Navigation Label</label>
                    <input class="w-full dark:bg-stone-700 w-full" v-model="element.name" />
                    <label for="">URL</label>
                    <input class="w-full dark:bg-stone-700" v-model="element.path" />
                </div>
                <nested-draggable :items="element.items" />
            </li>
        </template>
    </draggable>
</template>
<script>
import draggable from "vuedraggable";
import SporkInput from "@/Components/Spork/SporkInput.vue";

export default {
    props: {
        items: {
            required: true,
            type: Array
        }
    },
    components: {
        SporkInput,
        draggable
    },
    name: "nested-draggable",
    data() {
        return {
            open: false,
        };
    }
};
</script>
