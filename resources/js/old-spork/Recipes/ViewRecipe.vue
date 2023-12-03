<style scoped>

</style>

<template>
    <div class="w-full max-w-6xl mx-auto gap-4 grid grid-cols-4" v-if="recipe">
        <div class="flex flex-wrap col-span-4">
            <div class="w-full md:w-1/2">
                <div class="mb-4">
                    <div class="text-4xl">{{ recipe.name}}</div>
                    <div class="text-lg font-bold text-grey-darker">{{ recipe.headline}}</div>
                </div>
                <div class="mb-2" v-html="description"></div>
            </div>
            <div class="w-full md:w-1/2 sm:pl-4">
                <img :src="recipe.imageLink" class="rounded">
            </div>
        </div>

        <div class="flex flex-wrap col-span-4">
            <div class="mx-3 my-3 font-bold flex-grow">
                Preparation Time: {{ preperationTime }}
            </div>
        </div>
        <TrackButton event="recipe.cooked" :context="recipe" text="I made this recipe"></TrackButton>

        <div class="col-span-2 space-y-4"> 
            <div v-if="recipe.allergens && recipe.allergens.length > 0">
                <generic :items="recipe.allergens" :width="class_width(recipe.allergens)" class="shadow p-4 rounded bg-yellow-200 dark:bg-yellow-700">
                    <div class="text-2xl mb-4 font-bold items-center flex text-yellow-700 dark:text-white">
                        <svg class="fill-current text-yellow-700 dark:text-white w-6 h-6 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg>
                        Allergens
                    </div>
                </generic>
            </div>
            <div v-if="recipe.utensils && recipe.utensils.length > 0">
                <generic :items="recipe.utensils"  :width="class_width(recipe.utensils)" class="shadow p-4 rounded bg-white dark:bg-gray-600">
                    <div class="text-2xl mb-4 font-bold">
                        Utensils
                    </div>
                </generic>
            </div>
        </div>
        <ingredients :ingredients="recipe.ingredients" class="col-span-2"></ingredients>

        <div class="grid grid-cols-2 space-x-4 col-span-4"> 
            <div class="flex flex-wrap gap-4" v-for="(row, $i) in steps" :key="'recipe-step-'+$i">
                <div v-for="(step, $index) in row" :key="'recipe'+$i">
                    <div class="rounded overflow-hidden shadow-lg bg-white dark:bg-gray-600">
                        <img class="w-full" v-if="step.images" :src="step.images" :alt="step.name">
                        <div class="px-6 py-4">
                            <div v-if="step.ingredients && step.ingredients.length > 0">
                                <div class="my-2 text-xl">Ingredients</div>
                                <ul class="my-2">
                                    <li v-for="ingredient in step.ingredients">{{ ingredient.name }}</li>
                                </ul>
                                <hr>
                            </div>
                            <p class="text-grey-darker text-base" v-html="md(step.instructionsMarkdown).replace('•', 1+($i + $index) + '.')"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="recipe.wines && recipe.wines.length > 0" class="mt-4">
            <generic :items="recipe.wines" width="w-full" class="shadow p-4 mx-3 rounded bg-white dark:bg-gray-600">
                <div class="text-2xl mb-4 font-bold">
                    Suggested Wines
                </div>
            </generic>
        </div>
    </div>
</template>

<script>
    const array_chunks = (array, chunk_size) => {
        let arr = [];
        let chunk = [];
        for (let i = 0; i < array.length; i++) {
            let tmp = array[i];

            if (tmp.images === null) {
                if (chunk.length !== 0) {
                    arr.push(chunk);
                    chunk = [];
                }
                chunk.push(tmp);
                arr.push(chunk);
                chunk = [];
                continue;
            }

            if (chunk.length === chunk_size) {
                arr.push(chunk);
                chunk = [];
            }

            chunk.push(tmp);
        }

        return arr;
    }
    const markdown = require('markdown-it')()
    import Generic from './Generic'
    import Ingredients from './Ingredients'

    export default {
        components: {
            Generic,
            Ingredients,
        },
        data() {
            return {
                steps: [],
                recipe: null,
            }
        },
        computed: {
            description() {
                return markdown.render(this.recipe.descriptionMarkdown)
            },
            preperationTime() {
                return this.recipe.prepTime.replace('PT', '').replace('M', ' Minutes')
            },
        },
        methods: {
            class_width(row) {
                let count = row.length;

                if (count >= 5) {
                    return 'w-full md:w-1/2 lg:w-1/5';
                } else if (count >= 4) {
                    return 'w-full md:w-1/2 lg:w-1/4';
                } else if( count === 3) {
                    return 'w-full md:w-1/2 lg:w-1/3';
                } else if( count === 2) {
                    return 'w-full md:w-1/2 lg:w-1/2';
                }

                return 'w-full';
            },
            md (text) {
                return markdown.render(text)
            }
        },
        mounted() {
            axios.get('/api/food/recipes/' + this.$route.params.slug)
                .then(({ data }) => {
                    this.recipe = data;
                        if (this.recipe) {
                            this.steps = array_chunks(this.recipe?.steps, 2);
                        }
                    })


        }
    }
</script>
