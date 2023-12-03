<style scoped>

</style>

<template>
    <div class="w-full xl:w-1/2 2xl:w-1/3">
        <div class="mx-2 my-4 rounded overflow-hidden shadow bg-white dark:bg-gray-600">
            <img class="w-full" :src="recipe.imageLink.endsWith('.jpg') ? recipe.imageLink : recipe.imageLink + 'jpg'" alt="Sunset in the mountains">
            <div class="px-6 py-4">
                <div class="font-bold text-xl mb-2">
                    <router-link :to="'/food/recipes/' + recipe.slug">{{ recipe.name }}</router-link>
                    <div class="text-sm">{{ recipe.headline }}</div>
                </div>
                <p class="text-gray-600 dark:text-gray-300 text-base min-h-28">
                    {{ recipe.description }}
                </p>
            </div>
            <div class="px-6 pb-3 flex flex-wrap">
                <span v-if="recipe.prepTime" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mr-2 mb-2">#prep-time-{{ recipe.prepTime.replace('PT', '').toLowerCase()}}</span>
                <span v-if="recipe.difficulty === 1" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mr-2 mb-2">#difficulty-easy</span>
                <span v-else-if="recipe.difficulty === 2" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mr-2 mb-2">#difficulty-medium</span>
                <span v-else-if="recipe.difficulty === 3" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mr-2 mb-2">#difficulty-moderate</span>
                <span v-else-if="recipe.difficulty === 4" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mr-2 mb-2">#difficulty-hard</span>
                <span v-for="allergen in recipe.allergens" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mr-2 mb-2">#{{ allergen.type }}</span>
                <span v-if="recipe.wines.length > 0" class="bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-600 mb-2">#pairs-with-wine</span>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['recipe'],
        data() {
            return {}
        },
        mounted() {

        }
    }
</script>
