<script setup>
import Button from "@/Components/Button.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import Modal from "@/Components/Modal.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import {ref} from "vue";
import Multiselect from 'vue-multiselect';

const {
    show,
    type,
    identifiers,
    name,
} = defineProps({
    show: Boolean,
    type: String,
    name: String,
    identifiers: Array,
})

const $emit = defineEmits(['close', 'save', 'open']);

const tags = ref([]);
const tagToApply = ref(null);
const loading = ref(false);
const search= ref('');
const asyncFind = async (query) => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/crud/tags?filter[q]=${query}`);
        tags.value = data?.data;
    } finally {
        loading.value = false;
    }
}
const applyTags = async () => {
    console.log('Applying', identifiers,  );
    await Promise.all(identifiers.map(async (identifier) => {
        return await axios.post(`/api/crud/${name}/${identifier}/tags`, {
            tags: [...tagToApply.value].map((tag) => tag.id)
        })
    }));
    console.log('Applyed', identifiers, [...tagToApply.value]);
}
const clearAll= () => {
    tagToApply.value = null;
    search.value = '';
}
const close = () => $emit('close');
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="text-xl flex justify-between p-4">
            <slot name="modal-title">Create Modal</slot>
            <button @click="close" class="focus:outline-none">
                <DynamicIcon icon-name="XMarkIcon" class="w-6 h-6 stroke-current" />
            </button>
        </div>
        <div class="flex flex-col border-t border-stone-200 dark:border-stone-600 mt-2 p-4">
            {{identifiers}}
            <div>
                <Multiselect
                    v-model="tagToApply"
                    id="ajax"
                    label="name"
                    track-by="code"
                    placeholder="Type to search"
                    open-direction="bottom"
                    :options="tags"
                    :multiple="true"
                    :searchable="true"
                    :loading="loading"
                    :internal-search="false"
                    :clear-on-select="false"
                    :close-on-select="false"
                    :options-limit="300"
                    :limit="3"
                    :max-height="600"
                    :show-no-results="false"
                    :hide-selected="true"
                    @search-change="asyncFind"
                >
                    <template #tag="{ option, remove }">
                        <span class="custom__tag"><span>{{ option.name?.en }}</span>
                        <span class="custom__remove" @click="remove(option)">❌</span></span>
                    </template>

                    <template #no-result>
                        <span>Oops! No elements found. Consider changing the search query.</span>
                    </template>
                </Multiselect>
            </div>

            <div class="mt-4 flex justify-end gap-4">
                <SporkButton @click.prevent="close" primary medium>
                    Close
                </SporkButton>
                <SporkButton @click.prevent="applyTags" primary medium>
                    Apply Tag<span v-if="tagToApply?.length > 0"></span>
                </SporkButton>
            </div>
        </div>
</Modal>
</template>
