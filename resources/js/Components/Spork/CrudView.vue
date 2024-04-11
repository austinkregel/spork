<template>
    <div class="w-full">
        <div class="flex flex-wrap shadow rounded-lg gap-4">
            <div v-if="title" class="text-4xl mb-4 font-medium text-stone-900 dark:text-stone-200">
                {{ title }}
            </div>

            <SporkTable :headers="[]" :data="data">
                <template #table-top>
                    <div class="relative w-full flex flex-wrap justify-between items-center ">
                        <div class="flex gap-4 items-center">
                            <input @change="selectAll" :checked="data.length > 0 && selectedItems.length === data.length" type="checkbox">

                            <span v-if="selectedItems.length > 0" class="text-sm text-stone-700 dark:text-stone-300">{{ selectedItems.length }} selected</span>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="flex flex-wrap items-center dark:bg-stone-900 dark:text-slate-100 rounded" v-if="description?.actions?.length > 0 && selectedItems.length > 0">
                                <select v-model="actionToRun" class="border border-stone-300 rounded-lg flex-grow py-1 dark:border-stone-900 dark:bg-stone-900 dark:text-slate-100">
                                    <option v-for="action in description?.actions ??[]" :key="action" :value="action">{{ action.name }} ({{ selectedItems.length }})</option>
                                </select>

                                <button type="button" @click.prevent="() => {
                              executing = true;
                                $emit('execute', { selectedItems, actionToRun, next: () => { selectedItems = []; executing = false;} })
                            }">
                                    <PlayIcon v-if="!executing" class="w-6 h-6 stroke-current mr-2 text-green-400" />
                                    <ArrowPathIcon v-else class="w-6 h-6 stroke-current mr-2 text-blue-400" />
                                </button>
                            </div>
                            <button @click="filtersOpen= !filtersOpen" class="focus:outline-none flex flex-wrap items-center p-2 rounded-lg" :class="{'bg-stone-300 dark:bg-stone-700': filtersOpen, 'bg-stone-100 dark:bg-stone-900': !filtersOpen}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div v-if="filtersOpen" class="absolute z-10 bg-white dark:bg-stone-700 shadow-lg top-0 right-0 mt-14 mr-4 border border-stone-200 dark:border-stone-500 rounded-lg" style="width: 250px;">
                                <div class="bg-stone-100 dark:bg-stone-800 uppercase py-2 px-2 font-bold text-stone-500 dark:text-stone-400 text-sm rounded-t-lg">
                                    filters
                                </div>
                                <div class="flex flex-wrap items-center p-2">
                                    <select @change="(e) => router.reload({
                                        search: {limit: 321}
                                    })" class="border border-stone-300 rounded-lg w-full p-1 dark:border-stone-600 dark:bg-stone-600">
                                        <option value="15">15 items per page</option>
                                        <option value="30">30 items per page</option>
                                        <option value="100">100 items per page</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template #datum="{datum}">
                    <div class="flex items-center w-full">
                        <div class="w-6 mx-4">
                            <input type="checkbox" v-model="selectedItems" :value="datum">
                        </div>
                        <div class="flex-1 dark:text-stone-50 text-black">
                            <slot v-if="datum" class="flex-1" :data="datum" name="data"></slot>
                        </div>
                        <div v-if="typeof destroy === 'function'" class="flex items-center w-8">
                            <button type="button" @click.prevent="$emit('destroy', datum)">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </template>

                <template #no-data>
                    <div>
                        No data present in table
                    </div>
                </template>

                <template #table-bottom>
                    <div class="w-full dark:text-white flex justify-between flex-wrap bg-stone-100 dark:bg-stone-800 px-4 py-2">
                        <SporkButton :disabled="hasPreviousPage" :plain="true" :xlarge="true" :class="[!hasPreviousPage ? 'opacity-50 cursor-not-allowed': '']">Previous</SporkButton>
                        <div class="py-2">
                            {{ (currentPage * itemsPerPage) - itemsPerPage }} total items, {{ currentPage  }} of {{ paginator?.total}}
                        </div>
                        <SporkButton @click="() => {
                            getData(currentPage + 1);
                        }"
                         :disabled="hasNextPage"
                         plain
                         xlarge
                         :class="[!hasNextPage ? 'opacity-50 cursor-not-allowed': 'cursor-pointer']"
                        >Next</SporkButton>
                    </div>
                </template>
            </SporkTable>
        </div>
    </div>


    <div v-if="createOpen" class="fixed z-40 inset-0 flex items-center outline-none w-screen h-screen overflow-y-scroll">
        <div class="relative z-50 w-full md:max-w-3xl mx-auto max-h-screen overflow-y-auto p-4">
            <div class="w-full rounded p-4 dark:p-8 bg-white dark:bg-stone-800 shadow-lg text-left dark:text-white ">
                <div class="text-xl flex justify-between">
                    <slot name="modal-title">Create Modal</slot>
                    <button @click="createOpen = false" class="focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex flex-col border-t border-stone-200 mt-2 pt-4">
                    <slot name="form" :open-modal="() => createOpen = true"></slot>
                    <div class="mt-4 flex justify-end gap-4">
                      <SporkButton @click.prevent="async () => {
                                createOpen = false;
                            }"
                                   primary
                                   medium
                      >
                        Close
                      </SporkButton>
                      <SporkButton @click.prevent="async () => {
                                $emit('save', form);
                                createOpen = false;
                            }"
                                   primary
                                   medium
                      >
                        Save
                      </SporkButton>
                    </div>
                </div>
            </div>
        </div>
        <div @click="createOpen = false" v-if="createOpen" class="fixed z-30 cursor-pointer inset-0 flex items-center outline-none bg-stone-900/50"></div>
    </div>

</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { PlayIcon, XMarkIcon, ArrowPathIcon } from "@heroicons/vue/24/outline";
import SporkInput from './SporkInput.vue';
import SporkButton from './SporkButton.vue';
import { router, Link } from '@inertiajs/vue3';
import SporkSelect from "@/Components/Spork/SporkSelect.vue";
import SporkTable from "@/Components/Spork/SporkTable.vue";
const {
  form,
  title,
  singular,
  save,
  destroy,
  upload,
  data,
  paginator,
  settings,
  description,
    apiLink,
} = defineProps({
  form: {
    type: Object,
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
  singular: {
    type: String,
    default: 'singular',
  },

  // store
  save: {
    type: Function,
    default: null,
  },
  destroy: {
    type: Function,
    default: null,
  },
  upload: {
    type: Function,
    default: null,
  },

  // getters
  data: {
    type: Array,
    default: () => [],
  },
  paginator: {
    type: Object,
    default: () => ({}),
  },
  settings: {
    type: Object,
    default: () => ({})
  },
  description: {
    default :  () => ({
        actions: [],
      query_actions: [],
      fillable: [],
      fields: [],
      required: [],
      sorts: [],
        tags: [],
    })
  },
    plural: {
        type: String,
        default: ''
    },
    apiLink: {
        type: String,
        default: '/api/crud/models'
    }
})
const $emit = defineEmits([
    'index', 'destroy', 'save', 'execute'
])

const createOpen = ref(false);
const filtersOpen = ref(false);
const selectedItems = ref([]);
const itemsPerPage = ref(15);
const actionToRun = ref(null);
const searchQuery = ref(localStorage.getItem('searchQuery') ? localStorage.getItem('searchQuery') : '');
const debounceSearch = ref(null);
const executing = ref(false);

const selectedTagToApply = ref(null);

const hasPreviousPage = computed(() => {
  return paginator.prev_page_url !== null;
});

const hasNextPage = computed(() => {
  return paginator.next_page_url !== null;
});
const total = computed(() => {
  return paginator.total;
})
const currentPage = computed(() => {
  return paginator.current_page;
});
const lastPage = computed(() => {
  return Math.max(paginator.total / paginator.per_page, 1);
});


const hasErrors = (error) => {
  if (!form.errors) {
    return '';
  }

  return form.errors[error];
};
const selectAll = (event) => {
  if (event.target.checked) {
    selectedItems.value = data;
  } else {
    selectedItems.value = [];
  }
}
const clearSearch = () => {
  searchQuery.value = '';
}

onMounted(() => {
    $emit('index', {
        page: currentPage.value,
        limit: itemsPerPage.value
    })
})
const applyTag = async () => {
    if (!selectedTagToApply.value) {
        return;
    }

    await Promise.all(selectedItems.value.map(async (item) => {
        await axios.post(`${apiLink}/${item.id}/tags`, {
            tags: [selectedTagToApply.value]
        })
    }))

    router.reload({
        only: ['data', 'paginator', 'description']
    })
}
// const searchQuery = (newVal, oldVal) => {
//     if (debounceSearch !== null) {
//         clearTimeout(this.debounceSearch)
//     }
//     debounceSearch.value = setTimeout(() => {
//         localStorage.setItem('searchQuery', newVal);
//         this.$emit('index', {
//             page: 1,
//             limit: 15,
//             filter: {
//                 q: newVal
//             }
//         });
//
//     }, 400);
// }
</script>
