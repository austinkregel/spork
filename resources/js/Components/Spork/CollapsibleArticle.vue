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

</script>

<template>

<div>
    <Disclosure>
        <DisclosureButton>
            <div class="px-4 py-2 text-left flex gap-4 items-center">
                <div>
                    <img :src="article.external_rss_feed.profile_photo_path" :alt="article.external_rss_feed.name" v-if="article.external_rss_feed.profile_photo_path"  class="max-h-8 max-h-8"/>
                </div>
                <div>{{ article.headline }}</div>
            </div>
        </DisclosureButton>
        <DisclosurePanel >
            <div class="dark:bg-stone-950 p-4">
                <div class="text-sm tracking-wider leading-tight mb-4">{{ dateFormat(article.last_modified) }}</div>
                <div class="text-sm prose prose-sm dark:prose-invert w-full" v-html="article.content"></div>
            </div>
        </DisclosurePanel>
    </Disclosure>

</div>
</template>

<style scoped>

</style>
