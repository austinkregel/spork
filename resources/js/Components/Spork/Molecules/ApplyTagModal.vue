<script setup>
import { ref } from "vue";
import axios from "axios";
import Multiselect from 'vue-multiselect';
import GlassModal from "@/Components/Glass/GlassModal.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassPill from "@/Components/Glass/GlassPill.vue";

const props = defineProps({
    show: Boolean,
    type: String,
    name: String,
    identifiers: Array,
});

const emit = defineEmits(['close', 'save', 'open']);

const tags = ref([]);
const tagsToApply = ref(null);
const loading = ref(false);
const search = ref('');

const asyncFind = async (query) => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/crud/tags?filter[q]=${query}`);
        tags.value = data?.data;
    } finally {
        loading.value = false;
    }
};

const applyTags = async () => {
    if (props.identifiers.length === 0) return;

    await Promise.all(props.identifiers.map(async (identifier) => axios.post(
        `/api/crud/${props.name}/${identifier.id}/tags`,
        { tags: [...tagsToApply.value].map((tag) => tag.id) },
    )));

    emit('save');
    close();
};

const close = () => emit('close');
</script>

<template>
    <GlassModal :open="show" size="md" @close="close">
        <template #title>
            <slot name="modal-title">Apply tag</slot>
        </template>

        <div class="space-y-6">
            <div class="space-y-2">
                <div class="text-sm font-medium text-stone-700 dark:text-stone-200">Applying tag to</div>
                <div class="flex flex-wrap gap-2">
                    <GlassPill v-for="identifier in identifiers" :key="identifier.id" size="sm">
                        {{ identifier.name }}
                    </GlassPill>
                </div>
            </div>

            <div>
                <Multiselect
                    id="ajax"
                    v-model="tagsToApply"
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
                    :limit="5"
                    :max-height="300"
                    :show-no-results="true"
                    :hide-selected="true"
                    @search-change="asyncFind"
                >
                    <template #option="{ option }">
                        <span class="custom__tag"><span>{{ option.name?.en }}</span></span>
                    </template>
                    <template #tag="{ option, remove }">
                        <span class="custom__tag">
                            <span>{{ option.name?.en }}</span>
                            <button type="button" class="custom__remove" aria-label="Remove" @click="remove(option)">×</button>
                        </span>
                    </template>
                    <template #no-result>
                        <span>Oops! No elements found. Consider changing the search query.</span>
                    </template>
                </Multiselect>
            </div>
        </div>

        <template #footer>
            <GlassButton variant="secondary" size="sm" @click="close">Close</GlassButton>
            <GlassButton size="sm" @click="applyTags">
                Apply Tag<span v-if="tagsToApply?.length > 0">s</span>
            </GlassButton>
        </template>
    </GlassModal>
</template>
