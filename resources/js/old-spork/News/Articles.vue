<template>
    <div class="flex flex-wrap justify-center p-4 w-full h-screen overflow-y-scroll">
        <div class="w-full flex flex-col gap-4">
            <div class="flex flex-wrap justify-between">
                <div class="dark:text-stone-100 text-stone-700 font-bold text-3xl">Recent Headlines</div>

                <select @change="changeCategory" name="category" id="category" class="text-stone-600 dark:text-stone-300 bg-white py-1 px-2 rounded dark:bg-stone-600 dark:text-stone-100">
                    <option value="">All Categories</option>
                    <option value="business">Business</option>
                    <option value="entertainment">Entertainment</option>
                    <option value="general">General</option>
                    <option value="health">Health</option>
                    <option value="science">Science</option>
                    <option value="sports">Sports</option>
                    <option value="technology">Technology</option>
                </select>
            </div>
            <div role="list" class="w-full grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div v-for="(event, eventIdx) in $store.getters.articles" :key="eventIdx">
                    <div class="rounded overflow-hidden shadow bg-white dark:bg-stone-600">
                        <div class="bg-stone-900">
                            <img class="h-64 mx-auto" :src="event.urlToImage" :alt="event.name" />
                        </div>
                        <div class="px-4 my-2">
                            <div class="font-bold text-lg mb-2">
                                <a @click="() => delayMarkAsRead(event)"  target="_blank" :href="event.url" v-html="event.title"></a>
                                <div class="text-xs text-stone-400 dark:text-stone-300 mt-2 ">{{ dayjs(event.publishedAt).from(dayjs()) }}</div>
                            </div>
                            <p class="text-base" v-html="event.description"></p>
                        </div>
                        <div class="p-2 w-full flex justify-between text-xs">
                            <span class="inline-block bg-stone-100 dark:bg-stone-500 rounded-full px-3 py-1  font-semibold text-stone-600 dark:text-stone-300 dark:text-stone-200">
                                #{{event.source.name.toLowerCase().replace(' ','-')}}
                            </span>


                            <button @click="() => markAsRead(event)" class="text-red-500 hover:text-red-700  font-bold px-2 rounded">
                                Marked as read
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    components: {},
    name: "News",
    data() {
        return {
            dayjs,
            category: '',
        }
    },

    methods: {
        markAsRead(article) {
            this.$store.dispatch('articleHasBeenRead', article)
        },
        delayMarkAsRead(article) {
            setTimeout(() => {
                this.markAsRead(article)
            }, 100)
        },
        getNews() {
            this.$store.dispatch('getArticles')
        },
        changeCategory(e) {
            this.$store.dispatch('getArticles', { category: e.target.value})
        }
    },

    mounted() {
        this.getNews()
    }
}
</script>
