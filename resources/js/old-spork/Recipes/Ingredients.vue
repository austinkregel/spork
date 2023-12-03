<style scoped>

</style>

<template>
    <div class="bg-white dark:bg-gray-600 shadow p-4 rounded">
        <div class="flex flex-wrap">
            <div v-for="ingredient in ingredients" class="w-full md:w-1/2">
                <div class="flex flex-wrap items-center">
                    <img :src="ingredient.imageLink" alt="" style="max-height: 80px;">
                    <div>
                        <div class="text-lg font-bold">
                            {{ ingredient.name }}
                        </div>
                        <div>
                            {{ ingredient.pivot.amount }}
                            {{ ingredient.pivot.unit }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['ingredients'],
        data() {
            return {}
        },
        mounted() {

        }
    }
</script>
