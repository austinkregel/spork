<style lang="css">
@import "../../../../node_modules/vue-select/dist/vue-select.css";

.tribute-container ul, .tribute-container li {
    list-style: none;
    margin: 0;
    padding: 0;
}
</style>

<template>
    <div>
        <div class="flex flex-wrap border border-stone-500 dark:border-stone-700 dark:text-stone-50 rounded h-full" style="min-height: 400px;">
            <form class="w-1/3 h-auto border-r dark:border-stone-950" @submit.prevent="submitForm">
                <div class="mx-2">
                    <div class="text-sm uppercase text-stone-600 dark:text-stone-200 py-2 px-1 flex justify-between">
                        <span>Object to query</span>
                        <span>
                            <button type="button" @click.prevent="clearForm"
                                    class="text-orange bg-orange-50 dark:bg-orange-900 rounded px-1 focus:outline-none hover:bg-orange-200 hover:text-orange-600 hover:border-orange-600">
                                clear
                            </button>
                        </span>
                    </div>
                    <multiselect
                        v-model="model"
                        placeholder="Object to query..."
                        :options="models"
                        :multiple="false"
                        :clearable="true"
                        :get-option-label="option => option.pretty_name"
                     >
                    </multiselect>
                </div>

                <div v-if="model" class="mx-2">
                    <div class="text-sm uppercase text-stone-600 dark:text-stone-300 py-2 px-1">Select fields</div>
                    <multiselect
                        v-model="fields"
                        placeholder="Fields to query..."
                        :options="selectedModel.fields"
                        :clear-on-select="false"
                        :multiple="true"
                        :clearable="true"
                        :hide-selected="true"
                        :close-on-select="true"
                    >
                        <template slot-scope="{ option }">{{ option.name }}</template>
                    </multiselect>
                </div>

                <div v-if="model" class="mx-2">
                    <div class="text-sm uppercase text-stone-600 dark:text-stone-300 py-2 px-1">Relations to include</div>
                    <multiselect
                        v-model="includes"
                        placeholder="Relationships..."
                        :options="selectedModel.includes"
                        :clear-on-select="false"
                        :close-on-select="true"
                        :multiple="true"
                        :clearable="true"
                        :hide-selected="true"
                    >
                        <template slot="singleLabel" slot-scope="{ option }">{{ option.name }}</template>
                    </multiselect>
                </div>

                <div v-if="model" class="flex flex-wrap mx-2 relative text-xs" id="autocomplete-container">
                    <div class="w-full text-sm uppercase text-stone-600 dark:text-stone-300 py-2 px-1">Filter data</div>

                    <div class="flex  items-center gap-2 w-full" v-for="(filter, $i) in filters">
                        <multiselect
                            v-model="filters[$i]"
                            placeholder="Field..."
                            :options="filterTribute"
                            :preselect-first="true"
                            track-by="value"
                            label="value"
                            :clearable="true"
                            class="w-1/2 border-0 "
                        >
                        </multiselect>
                        <div class="w-1/2">
                            <SporkInput v-model="filters[$i].text" />
                        </div>

                        <div>
                            <button type="button" @click.prevent="() => filters = filters.filter((d, index) => index !== $i )">
                                <DynamicIcon icon-name="TrashIcon" class="w-4 h-4 text-red-500 dark:text-red-400" />
                            </button>
                        </div>
                    </div>

                    <div class="flex justify-end w-full">
                        <button type="button" @click.prevent="addFilter" class="flex items-center border border-stone-500 dark:border-stone-600 text-stone-500 mt-2 bg-stone-50 dark:bg-stone-700 dark:text-stone-50 rounded px-2 py-1 focus:outline-none hover:bg-stone-200 hover:text-stone-600 hover:border-stone-600" style="font-size: 1.2em;">
                            <svg class="w-4" data-600reader-inline-stroke="" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>

                            filter
                        </button>
                    </div>
                </div>

                <div v-if="model" class="mx-2">
                    <div class="text-sm uppercase text-stone-600 py-2 px-1">Action</div>
                    <multiselect
                        v-model="action"
                        placeholder="get, paginate, or first..."
                        :options="selectedModel.query_actions"
                        :hide-selected="true"
                        :close-on-select="true"
                        :clearable="true"
                        :preselect-first="true"
                    >
                    </multiselect>
                </div>

                <div v-if="model" class="flex justify-between mr-2">
                    <select name="page" class="border border-stone-200 dark:border-stone-700 dark:bg-stone-800 bg-white p-0 m-2 flex-grow" v-if="response.data" v-model="page">
                        <option v-for="i in range(1, response.last_page || 1)" :value="i">Page {{ i }}</option>
                    </select>
                    <span v-else></span>

                    <ActionButton type="submit" :action="getData" icon="save-disk" classes="text-stone-900 dark:text-stone-50" button-classes="border rounded px-4 py-2 text-black bg-white dark:bg-stone-600 dark:border-stone-600 dark:text-white  my-2">
                        Execute
                    </ActionButton>
                </div>
            </form>
            <div class="w-2/3 bg-stone-200 dark:bg-stone-800 dark:text-stone-200">
                <div>
                    <input type="text" v-model="url" class="w-full py-1 px-1 text-stone-600 dark:text-indigo-300 dark:bg-stone-950 text-sm">
                </div>
                <vue-json-pretty
                    :data="response || {message: 'No model found'}"
                    :show-length="true"
                    :show-line="true"
                    :highlight-selected-node="true"
                    :highlight-mouseover-node="true"
                ></vue-json-pretty>
            </div>
        </div>
    </div>
</template>
<script lang="js">
import VueJsonPretty from 'vue-json-pretty'
import ActionButton from "@/Components/ActionButton.vue";
import { buildUrl } from '@kbco/query-builder';
import Multiselect from "vue-select";
import 'vue-json-pretty/lib/styles.css';
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";

export default {
    components: {
        DynamicIcon,
        SporkButton,
        SporkInput,
        ActionButton,
        VueJsonPretty,
        Multiselect,
    },
    props: ['models'],
    data() {
        return {
            modelFields: this.models.reduce((objects, model) => {
                return Object.assign(objects, {
                    [model.name]: model,
                })
            }, {}),
            Object,
            action: null,
            model: null,
            includes: [],
            fields: [],
            filters: [],
            filter: '',
            page: 1,

            vueOptions: {
                autocompleteMode: true,
                selectClass: 'selected',
                menuContainer: document.getElementById('autocomplete-container'),
            },
            response: {
                press: "The execute button..."
            }
        }
    },
    methods: {
        tribute(key) {
            return this.selectedModel[key].map(item => ({value: item}))
        },

        getData() {
            return axios.get(this.url).then(({data}) => {
                if (!data) {
                    data = []
                }
                let count = data.length;
                this.response={}
                let limit = 400;
                if (count > limit) {
                    this.response = data.splice(1, 400)

                    izitoast.error({
                        title: 'SCOPE THAT QUERY!',
                        message: 'Hey now. You requested too much data... Try filtering down your query to not have us blow up your browser.',
                        animateInside: false,
                        timeout: 10 * 1000
                    });
                } else {
                    this.response = data;
                }

            })
        },

        clearForm() {
            this.action = null;
            this.includes = [];
            this.fields = [];
            this.filters = [];
            this.filter = '';
            this.page = 1;
        },

        addFilter() {
            this.filters.push({
                name: '',
                value: ''
            })
        },

        submitForm() {
            this.getData()
        },

        range(min, max) {
            let arrayRange = [];
            for (let i = min; i <= max; i++) {
                arrayRange.push(i);
            }
            return arrayRange;
        }
    },
    computed: {
        selectedModel() {
            return this.modelFields?.[this.model?.name];
        },
        filterTribute() {
            if (!this.selectedModel) {
                return []
            }
            this.selectedModel.filters;
            return this.tribute('filters')
        },
        filterObject() {
            return this.filters.reduce((filters, filter) => {
                if (!filter.value || !filter.text) {
                    return filters;
                }

                filters[filter.value] = filter.text;
                return filters;
            }, {});
        },
        pagePart() {
            if (!this.response.data) {
                return {}
            }

            return {
                page: this.page
            }
        },
        url() {
            console.log(Array.isArray(this.fields))
            return buildUrl('/api/crud/' + this.model?.name, Object.assign({
                fields: this.fields.length > 0 ? {
                    [this.model.name]: this.fields.join(',')
                } : null,
                include: this.includes,
                action: this.action,
                filter: this.filterObject,
            }, this.pagePart))
        },
        actionInputs() {
            return this.models.reduce((models, model) => {
                return this.modelFields[model.name].actions.reduce((actions, action) => {
                    return actions;
                }, models);
            }, {})
        }
    },
    mounted() {
        let that = this;

        let keybindingHandler = (e) => {
            if (!(e.keyCode == 13 && e.metaKey)) return;

            that.getData()
        };

        document.body.removeEventListener('keydown', keybindingHandler);
        document.body.addEventListener('keydown', keybindingHandler)
    }
}
</script>
