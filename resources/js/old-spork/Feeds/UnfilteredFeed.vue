<template>
    <div class="mx-4">
        <div class="container mx-auto">
            <div class="flex flex-wrap shadow w-full p-2">
                <div class="flex flex-wrap shadow">
                    <div class="text-4xl font-medium text-blue-900 dark:text-stone-200">
                        Your feed
                    </div>

                    <div class="w-full mt-4 flex flex-wrap items-center justify-between">
                        <div class="relative w-3/4">
                            <spork-input type="text" placeholder="Search..." class="pl-10"/>
                            <div class="absolute top-0 left-0 ml-3 mt-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="relative flex flex-1 max-w-2xl text-stone-700 dark:text-stone-300 items-center">
                            <div>
                                <button @click="filtersOpen= !filtersOpen"
                                        class="focus:outline-none flex flex-wrap items-center p-2 rounded-lg"
                                        :class="{'bg-stone-300 dark:bg-stone-700': filtersOpen, 'bg-stone-100 dark:bg-stone-900': !filtersOpen}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                    </svg>
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <div v-if="filtersOpen"
                                     class="absolute z-10 bg-white dark:bg-stone-700 shadow-lg top-0 right-0 mt-14 mr-4 border border-stone-200 dark:border-stone-500 rounded-lg"
                                     style="width: 250px;">
                                    <div
                                        class="bg-stone-100 dark:bg-stone-800 uppercase py-2 px-2 font-bold text-stone-500 dark:text-stone-400 text-sm rounded-t-lg">
                                        filters
                                    </div>
                                    <div class="flex flex-wrap items-center p-2">
                                        <select v-model="itemsPerPage"
                                                class="border border-stone-300 rounded-lg w-full p-1 dark:border-stone-600 dark:bg-stone-600">
                                            <option value="15">15 items per page</option>
                                            <option value="30">30 items per page</option>
                                            <option value="100">100 items per page</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <RssFeeds></RssFeeds>
        </div>
    </div>

</template>

<script>
import RssFeeds from './RssFeeds';

export default {
    name: "UnfilteredFeed",
    components: {
        RssFeeds,
    },
    data() {
        return {
            filtersOpen: false,
            itemsPerPage: 5,
        }
    },
    mounted() {
        this.$store.dispatch('getFeed')

    }
}
</script>

<style scoped>

</style>
