<style>
    label {
        display: block;
        padding-left: 15px;
        text-indent: -15px;
    }
    input[type=checkbox] {
        width: 13px;
        height: 13px;
        padding: 0;
        margin:0;
        vertical-align: bottom;
        position: relative;
        top: -1px;
        *overflow: hidden;
    }

    .loader{
        height: 5rem;
        width: 10rem;
        text-align: center;
        padding: 1em;
        display: inline-block;
        vertical-align: top;
    }

    /*
      Set the color of the icon
    */
    svg.loader path,
    svg.loader rect{
        fill: #FF6700;
    }
</style>

<template>
    <div class="w-full relative">
        <div class="w-full mx-auto row justify-content-center shadow-lg top-0">
            <div class="flex flex-wrap w-full">
                <div class="bg-white dark:bg-stone-600 w-full mx-2">
                    <input v-model="search.query" type="text" @keyup.enter="searchQuery" class="sticky placeholder-stone-300 w-full p-3 border-b border-stone-200 dark:border-stone-500 dark:bg-stone-500 text-grey-800 focus:outline-none" placeholder="Search for things...">
                    <div class="flex flex-wrap">
                        <div class="w-full border-t border-l-none lg:border-t-none border-stone-200 dark:border-stone-400 lg:flex-1">
                            <div class="font-bold p-3 w-full">Avoid Allergens...</div>
                            <div style="max-height: 150px;" class="overflow-auto flex flex-col">
                                <label class="text-monospace text-sm inline-block pl-6 ml-1" v-for="allergy in allergies">
                                    <input @change="searchQuery" v-model="search.allergies" class="align-middle ml-3" :value="allergy.type" type="checkbox"> {{ allergy.name }}
                                </label>
                            </div>
                        </div>

                        <div class="w-full border-t border-l-none lg:border-t-none lg:border-l border-stone-200 dark:border-stone-400 lg:flex-1">
                            <div class="font-bold p-3 w-full">Has ingredients...</div>
                            <div style="max-height: 150px;" class="overflow-auto flex flex-col">
                                <label class="text-monospace text-sm inline-block pl-6 ml-1" v-for="ingredient in containsIngredients">
                                    <input @change="searchQuery" v-model="search.ingredients" class="align-middle ml-3" :value="ingredient.type" type="checkbox"> {{ ingredient.name }}
                                </label>
                            </div>
                        </div>

                        <div class="w-full border-t border-l-none lg:border-t-none lg:border-l border-stone-200 dark:border-stone-400 lg:flex-1">
                            <div class="font-bold p-3 w-full">Takes about...</div>
                            <div style="max-height: 150px;" class="overflow-auto flex flex-col">
                                <label class="text-monospace text-sm inline-block pl-6 ml-1" v-for="time in prepTime">
                                    <input @change="searchQuery" v-model="search.prepTime" class="align-middle ml-3" :value="time" type="checkbox"> {{ preptimeFormat(time) }}
                                </label>
                            </div>
                        </div>

                        <div class="w-full border-t border-l-none lg:border-t-none lg:border-l border-stone-200 dark:border-stone-400 lg:flex-1 flex-col flex">
                            <div class="font-bold p-3 w-full">Difficulty</div>
                            <div style="max-height: 150px;" class="overflow-auto flex flex-col pl-3">
                                <label class="text-monospace text-sm inline-block">
                                    <input @change="searchQuery" v-model="search.difficulty" value="0" class="align-middle" type="radio"> Any
                                </label>
                                <label class="text-monospace text-sm inline-block">
                                    <input @change="searchQuery" v-model="search.difficulty" value="1" class="align-middle" type="radio"> Easy
                                </label>

                                <label class="text-monospace text-sm inline-block">
                                    <input @change="searchQuery" v-model="search.difficulty" value="2" class="align-middle" type="radio"> Medium
                                </label>

                                <label class="text-monospace text-sm inline-block">
                                    <input @change="searchQuery" v-model="search.difficulty" value="3" class="align-middle" type="radio"> Moderate
                                </label>

                                <label class="text-monospace text-sm inline-block">
                                    <input @change="searchQuery" v-model="search.difficulty" value="4" class="align-middle" type="radio"> Probably Hard...
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap rounded-t-lg" :style="'min-height:' + preservedHeight + 'px;'" ref="wrapper" v-if="!search.loading">
            <recipe v-for="recipe in recipes.data" :recipe="recipe" :key="recipe.id"></recipe>

            <div class="w-full flex items-center justify-center">
                <button class="load-more py-2 px-4 rounded-lg bg-stone-200 hover:bg-stone-300 dark:bg-stone-800 text-stone-800 dark:text-stone-200 font-bold" @click="loadMore">Load More</button>
            </div>
        </div>
        <div class="w-full text-center relative" :style="'min-height:' + preservedHeight + 'px;'" v-else>
            <div class="loader mx-auto absolute inset-x-0 bottom-0 " :style="'top:' +(preservedHeight-600)+'px;'">
                <svg class="mx-auto loader -ml-8" version="1.1" id="loader-1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                     width="40px" height="40px" viewBox="0 0 40 40" enable-background="new 0 0 40 40" xml:space="preserve">
                    <path opacity="0.2" fill="#000" d="M20.201,5.169c-8.254,0-14.946,6.692-14.946,14.946c0,8.255,6.692,14.946,14.946,14.946s14.946-6.691,14.946-14.946C35.146,11.861,28.455,5.169,20.201,5.169z M20.201,31.749c-6.425,0-11.634-5.208-11.634-11.634c0-6.425,5.209-11.634,11.634-11.634c6.425,0,11.633,5.209,11.633,11.634C31.834,26.541,26.626,31.749,20.201,31.749z"/>
                    <path fill="#000" d="M26.013,10.047l1.654-2.866c-2.198-1.272-4.743-2.012-7.466-2.012h0v3.312h0C22.32,8.481,24.301,9.057,26.013,10.047z">
                        <animateTransform attributeType="xml"
                          attributeName="transform"
                          type="rotate"
                          from="0 20 20"
                          to="360 20 20"
                          dur="0.5s"
                          repeatCount="indefinite"/>
                    </path>
                </svg>
            </div>
        </div>
    </div>
</template>

<script>
    import Recipe from './Recipe';

    export default {
        name: 'all-recipes',
        components: {
            Recipe
        },
        props: [
            'originalRecipes',
            'prepTime',
            'containsIngredients',
            'pairsWithWine',
            'allergies'
        ],
        data () {
            return {
                recipes: {},
                search: {
                    query: '',
                    allergies: [],
                    pairs: [],
                    ingredients: [],
                    difficulty: 0,
                    prepTime: [],
                    loading: false
                },
                preservedHeight: 0,
            }
        },
        methods: {
            searchQuery() {
                this.search.loading = true;
                let searchingFor = {};

                if (this.search.query.length !== 0) {
                    searchingFor['query'] = this.search.query
                }

                if (this.search.allergies.length !== 0) {
                    searchingFor['allergies'] = this.search.allergies
                }

                if (this.search.pairs.length !== 0) {
                    searchingFor['pairs'] = this.search.pairs
                }

                if (this.search.ingredients.length !== 0) {
                    searchingFor['ingredients'] = this.search.ingredients
                }

                if (this.search.prepTime.length !== 0) {
                    searchingFor['prepTime'] = this.search.prepTime
                }

                if (this.search.difficulty != 0) {
                    searchingFor['difficulty'] = this.search.difficulty
                }

                axios.post('/api/food/search', searchingFor)
                    .then(res => {
                        this.recipes = res.data;
                        this.search.loading = false;
                    }).catch(e => {
                        this.search.loading = false;
                    })

            },
            getNewRecipes(page = 1) {
                this.search.loading = true;
                axios.get('/api/food/recipes?page=' + page)
                    .then(({ data }) => {
                        if (page != 1) {
                            const { data: recipes, ...pagination } = data;
                            const { data: oldRecipes } = this.recipes;
                            this.recipes = {
                                ...pagination,
                                data: oldRecipes.concat(recipes)
                            }
                        } else {
                            this.recipes = data;
                        }

                        this.search.loading = false;
                    })
            },
            preptimeFormat(time) {
                return time.replace('PT', '')
                    .replace('M', ' minutes')
            },
            loadMore() {
                this.preservedHeight = this.$refs.wrapper.clientHeight
                this.getNewRecipes(this.recipes.current_page + 1)
            }
        },
        mounted() {
            this.getNewRecipes();
        }
    }
</script>
