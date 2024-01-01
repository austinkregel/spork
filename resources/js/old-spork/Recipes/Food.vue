<template>
    <div class="flex flex-wrap mt-4 w-full">
        <div class="w-full py-2 px-4 text-2xl font-bold text-stone-800 dark:text-stone-200">Food Dashboard</div>

        <div class="w-full px-2 pt-4">

            <all-recipes
                    :original-recipes="recipes"
                    :prep-time="prepTime"
                    :contains-ingredients="containsIngredients"
                    :pairs-with-wine="pairsWithWine"
                    :allergies="allergies"
            ></all-recipes>
        </div>

    </div>
</template>

<script>
import AllRecipes from './AllRecipes.vue'

export default {
    components: {
        AllRecipes
    },
    data() {
        return {
            recipes: [],
            prepTime: [],
            containsIngredients: [],
            pairsWithWine: [],
            allergies: [],
        }
    },
    methods: {
        getRecipes() {
            axios.get('/api/food/recipes')
                .then(response => {
                    this.recipes = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        getPrepTime() {
            axios.get('/api/food/prep-times')
                .then(response => {
                    this.prepTime = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        getContainsIngredients() {
            axios.get('/api/food/ingredient-families')
                .then(response => {
                    this.containsIngredients = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        getPairsWithWine() {
            axios.get('/api/food/wine-attributes')
                .then(response => {
                    this.pairsWithWine = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
        getAllergies() {
            axios.get('/api/food/allergens')
                .then(response => {
                    this.allergies = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },

    },
    mounted() {
        this.getRecipes();
        this.getPrepTime();
        this.getContainsIngredients();
        this.getPairsWithWine();
        this.getAllergies();
    }
}
</script>
