<script setup>
import PetoskeyLayout from "@/Layouts/PetoskeyLayout.vue";
// This component uses data from the OpenWeatherAPI
import WeatherHeader from "./WeatherHeader.vue";
import ListComponent from "../../Components/Spork/Molecules/ListComponent.vue";
import {defineProps, ref, onMounted, onBeforeUnmount, onUnmounted} from 'vue';
import Grid from "../../Components/Grid.vue";
import dayjs from "dayjs";
defineProps({
    weather: Object,
    news: Object,
    events: Object,
    articles: Object,
    title: String,
});

const timeInterval = ref(null);
const isLightOutside = ref(dayjs().hour() > 6 && dayjs().hour() < 20)
const isGoldenHour = ref(false)
const now = ref(dayjs());

const figureOutTheGradient = () => {
    if (isLightOutside.value && !isGoldenHour.value) {
        return 'from-blue-500 to-sky-400 dark:from-blue-800 dark:to-sky-800'
    } else if (isGoldenHour.value && !isLightOutside.value) {
        return 'from-blue-800 to-amber-900'
    } else if (isGoldenHour.value && isLightOutside.value) {
        return 'from-amber-500 to-blue-600 dark:from-amber-800 dark:to-blue-800'
    }
    return 'from-sky-800 to-blue-800'

}
const gradientShading = ref(figureOutTheGradient());
const formatDate = (date) => {
    return dayjs(date).format('MMMM D, YYYY h:mm a')
}
onMounted(() => {
    timeInterval.value = setInterval(() => {
        now.value = dayjs();
        if (weather?.sunset === undefined) {
            return;
        }
        const sunset = dayjs.unix(weather.sunset);
        const sunrise = dayjs.unix(weather.sunrise);
        const goldenHourStart = sunset.subtract(45, 'minutes');
        const goldenHourEnd = sunset.add(30, 'minutes');

        isLightOutside.value = now.isAfter(sunset) || now.isBefore(sunrise) ;
        isGoldenHour.value = now.isAfter(goldenHourStart) && now.isBefore(goldenHourEnd);
        gradientShading.value = figureOutTheGradient();

    }, 1000)
})

onUnmounted(() => {
    clearInterval(timeInterval)
})
</script>

<template>
    <PetoskeyLayout title="Today, in Petoskey Michigan">
        <div
            class="p-8 bg-gradient-to-r relative flex items-top justify-center min-h-screen sm:items-center sm:pt-0 flex-col"
            :class="[gradientShading]"
        >
            <div
                class="max-w-7xl relative my-8 w-full bg-slate-50 dark:bg-slate-900 rounded p-4 flex flex-wrap rounded-lg shadow-lg">
                <WeatherHeader :now="now" :weather="weather" class=" sticky top-0 bg-slate-50 dark:bg-slate-900"/>
                <div class="bg-gradient-to-r from-blue-400 to-indigo-400 h-px my-4 w-full"></div>
                <div class="flex flex-wrap gap-6 w-full justify-center mt-4 divide-y divide-slate-300">
                    <div v-for="article in articles.data" class=" flex flex-wrap overflow-hidden dark:text-slate-50 text-slate-900 pt-4">
                        <div class="article-content max-w-4xl mx-auto border-b border-slate-900 text-wrap p-4 w-full" v-html="article.content"></div>
                        <div class="flex justify-between w-full items-center max-w-4xl mx-auto">
                            <div class="flex flex-col px-4 py-2">
                                <div class="text-lg">
                                    {{ article.author.name}}
                                </div>
                                <div class="text-sm">
                                    {{formatDate(article.last_modified)}}
                                </div>
                            </div>

                            <div class="text-sm text-center mx-4 underline font-semibold">
                                <a :href="article.url" target="_blank">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </PetoskeyLayout>
</template>

<style>
    .article-content img {
        max-height: 300px;
        border-radius: 5px;
    }
    .article-content {
        max-height:  400px;
        overflow-x: hidden;
        overflow-y: auto
    }
</style>
