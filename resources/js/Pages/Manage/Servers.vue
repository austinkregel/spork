<template>
    <Manage>
            <crud-view
                :form="form"
                singular="Server"
                @destroy="() => { }"
                @index="({ page, limit, ...args }) => fetch({ page, limit, ...args })"
                @execute="() => {}"
                @save="() => {}"
                :save="() => {}"
                :data="data"
                :paginator="paginator"
            >
                <template #modal-title>
                    <div>
                        Create a Server
                    </div>
                </template>
                <template v-slot:data="{ data }">
                    <div class="flex flex-col">
                        <div class="text-lg text-left">
                            {{ data.name }}
                        </div>
                      <div>{{data }}</div>
                        <div class="flex flex-wrap gap-2">
                            <div class="text-xs dark:text-stone-300">
                                {{ data?.tags?.map(tag => tag?.name?.en) }}
                            </div>
                        </div>
                    </div>
                </template>
                <template #no-data>No Servers</template>

                <template #form>
                    <div>
                        <div class="grid grid-cols-6 gap-4 mt-2">
                            <div class="col-span-6">
                                <label for="name" class="block text-sm font-medium">Name</label>
                                <spork-input v-model="form.name" type="text" name="name" id="name" />
                            </div>

                            <div class="col-span-6">
                                <label for="name" class="block text-sm font-medium">Type</label>
                                <spork-input v-model="form.settings.type" type="text" name="name" id="name" />
                            </div>

                        </div>
                    </div>
                </template>
            </crud-view>
    </Manage>
</template>
<script setup>
import AppLayout from "../../Layouts/AppLayout.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import CrudView from '@/Components/Spork/CrudView.vue'
import { defineProps, ref } from 'vue';
import Manage from "@/Layouts/Manage.vue";
const { description, plural, singular, link, paginator, data } = defineProps({
    description: String,
    plural: String,
    singular: String,
    link: String,
    paginator: Object,
    data: Array,
});

const form = ref({
  settings: {}
});
const fetch = () => {

}
</script>
