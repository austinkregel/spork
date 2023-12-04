<template>
    <div class="m-4 flex flex-col gap-16">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <div class="px-4 sm:px-0">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-50">Groups</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 dark:text-gray-200">
                       Even transactions from the same vendors could vary over time, or between transactions, groups help you define how to classify transactions.
                    </p>
                </div>
            </div>
            <div class="md:mt-0 md:col-span-2 p-4 shadow rounded-lg bg-white dark:bg-gray-600">
                <SporkLabel>Name</SporkLabel>
                <SporkInput v-model="form.name" class="mb-2"/>

                <conditions v-model="form.conditions"></conditions>

                <div class="w-full flex flex-col gap-2 text-xs px-1">
                    <label class="mx-2 gap-4 flex w-full items-center">
                        <input value="true" type="radio" v-model="form.must_all_conditions_pass" class="-ml-4" />
                        <span class="ml-2">All conditions must pass</span>
                    </label>
                    <label class="mx-2 gap-4 flex w-full items-center">
                        <input value="false" type="radio" v-model="form.must_all_conditions_pass" class="-ml-4" />
                        <span class="ml-2">Only 1 condition must pass</span>
                    </label>
                </div>

                <SporkButton @click="saveGroup" :disabled="loading" primary medium class="mt-2">
                    Save
                </SporkButton>
            </div>
        </div>


        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1"></div>
            <div class="md:mt-0 md:col-span-2 p-4 shadow rounded-lg bg-white dark:bg-gray-600">

                <div v-for="group in $store.getters.features?.financeGroup" :key="group.id+'group'" class="flex flex-wrap w-full items-center justify-between gap-4">
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-slate-50">{{ group.name }}</p>
                        <p class="text-sm text-gray-500 dark:text-slate-400">{{ (group.conditionals ?? []).map(param => param.parameter + ' ' + param.comparator + ' ' + param.value).join(', ') }}</p>
                    </div>

                    <button @click="() => $store.dispatch('deleteFeature', group)">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>

            </div>
        </div>

        <pre>{{ $store.getters.features?.financeGroup }}</pre>
    </div>
</template>

<script>
    import {HomeIcon, PencilIcon, PhoneIcon, CloudIcon, TrashIcon, OfficeBuildingIcon} from "@heroicons/vue/outline";

    import HeaderMapping from "@components/HeaderMapping";
    import LinkAccount from "./LinkAccount";
    import SporkButton from "../../components/SporkButton.vue";
    import SporkInput from "../../components/SporkInput.vue";
    import SporkLabel from "../../components/SporkLabel.vue";

    export default {
        name: "Finance.Settings",
        components: {
            HeaderMapping,
            OfficeBuildingIcon,
            TrashIcon,
            LinkAccount,
            SporkButton,
            SporkInput,
            SporkLabel
        },
        data() {
            return {
                open: false,
                form: {
                    name: '',
                    conditions: [],
                    must_all_conditions_pass: false,
                },
                loading: false,
            };
        },
        methods: {
            async saveGroup() {
                this.loading = true;
                const feature = await this.$store.dispatch('createFeature', {
                    name: this.form.name,
                    feature: 'financeGroup',
                    settings: {
                        must_all_conditions_pass: this.form.must_all_conditions_pass,
                    },
                })

                for (let conditionIndex in this.form.conditions) {
                    const condition = this.form.conditions[conditionIndex];

                    if (!condition || !condition.comparator) {
                        continue;
                    }

                    console.log('condition from for each', {
                        comparator: condition.comparator,
                        parameter: condition.parameter,
                        value: condition.value,
                    });
                    await this.$store.dispatch('createCondition', {
                        comparator: condition.comparator,
                        parameter: condition.parameter,
                        value: condition.value,
                        conditionable_type: 'App\\Models\\FeatureList',
                        conditionable_id: feature.id,
                    })
                }

                this.$store.dispatch('fetchFeatures', {
                })
                this.loading = false;
            }
        },
        computed: {
            routes() {
                return [
                    {
                        name: 'Home',
                        href: '#',
                        icon: HomeIcon,
                        current: false,
                    },
                ]
            }
        },
        mounted() {

        }
    }

</script>

<style scoped>

</style>
