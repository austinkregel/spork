<script setup>
import {
    Disclosure,
    DisclosureButton,
    DisclosurePanel,
} from '@headlessui/vue'
import dayjs from "dayjs";

const { article } = defineProps({
    article: Object,
})

const dateFormat = (date) => {
    return dayjs(date).format('MMMM D, YYYY HH:mm:A');
}

const markAsRead = () => {
    console.log('mark as read');
}
</script>

<template>

<div>
    <Disclosure>
        <DisclosureButton>
            <div class="px-4 py-2 text-left flex gap-4 items-center">
                <div class="w-8">
                    <img :src="article.external_rss_feed.profile_photo_path" :alt="article.external_rss_feed.name" v-if="article.external_rss_feed.profile_photo_path"  class="h-8 w-8"/>
                </div>
                <div class="overflow-ellipsis w-full">{{ article.headline }}</div>
            </div>
        </DisclosureButton>
        <DisclosurePanel>
            <div @click="markAsRead" class="dark:bg-stone-950 p-4">
                <div class="text-sm tracking-wider leading-tight mb-4">{{ dateFormat(article.last_modified) }}</div>
                <div class="text-sm prose prose-sm dark:prose-invert w-full" v-html="article.content"></div>
                <a target="_blank" :href="article.url" class="mt-4 text-sm underline">Continue reading...</a>
            </div>
        </DisclosurePanel>
    </Disclosure>

</div>
</template>

<style scoped>

</style>
