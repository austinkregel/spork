<template>
    <div class="flex flex-wrap mt-4 w-full">
        <div class="w-full py-2 px-4 text-2xl font-bold text-gray-800 dark:text-gray-200">Food Dashboard</div>
    


        <div class="w-full px-2 pt-4">
            <view-recipe :recipe="recipe"></view-recipe>
        </div>

    </div>
</template>

<script>
import ViewRecipe from './ViewRecipe';

export default {
    components: {
        ViewRecipe,
    },
    props: ['recipe'],
    data() {
        return {
            recipes: [],
        }
    },
    methods: {
        getRecipe() {
            console.log(this)

            axios.get('/api/food/')
                .then(response => {
                    this.recipes = response.data;
                })
                .catch(error => {
                    console.log(error);
                });
        },
    },
    mounted() {
        this.getRecipe();
    }
}
</script>
