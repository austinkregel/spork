<template>
    <ul role="list" class="grid grid-cols-2 ">
        <li v-for="(event, eventIdx) in $store.getters.feeds" :key="eventIdx" class="w-full p-2 group">
            <div class="relative rounded shadow bg-white dark:bg-gray-600">
                <div class="absolute bottom-0 z-10 bg-gray-900 hidden group-hover:block h-0 group-hover:h-75 w-full bg-slate-100" v-if="event.attachment">
                    <img class="h-75 mx-auto" :src="event.attachment" :alt="event.name" />
                </div>
                <div class="px-4 pt-4 ">
                    <div class="font-bold text-lg mb-2">
                        <a @click="() => delayMarkAsRead(event)"  target="_blank" :href="event.url" v-html="event.headline" class="line-clamp-2"></a>
                        <span class="inline-block rounded-full font-semibold text-gray-700 dark:text-slate-300">
                            {{event.author?.name}}
                        </span>

                        <div class="text-xs text-gray-400 dark:text-gray-300 mt-2 ">{{ dayjs.utc(event.last_modified).from(dayjs()) }}</div>
                    </div>
                    <p class="line-clamp-2 text-base" v-html="event.content"></p>
                </div>
                <div class="p-2 w-full flex justify-between text-sm">

                    <button @click="() => markAsRead(event)" class="text-red-500 hover:text-red-700  font-bold px-2 rounded">
                        Marked as read
                    </button>
                    <favorite-button :favorite="event" />

                </div>
            </div>
        </li>
    </ul>
</template>

<script>
import FavoriteButton from "@components/FavoriteButton.vue";

export default {
    components: {FavoriteButton},
    data() {
        return {
            dayjs,
        }
    }
}
</script>
