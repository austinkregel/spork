<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200 leading-tight">
                Notifications
            </h2>
        </template>
        <div class="">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex gap-5">
                <div class="m-8 flex flex-col w-full max-w-3xl">
                    <div v-for="notification in page.props.notifications" :key="notification.id" class="flex gap-4 flex-col">
                        <NotificationWithAction :notification="notification">
                            <template #actions>
                                <div class="flex space-x-7 gap-6">
                                    <button type="button" class="rounded-md bg-white text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Undo</button>
                                    <button type="button" class="rounded-md bg-white text-sm font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Dismiss</button>
                                </div>
                            </template>
                        </NotificationWithAction>
                        <NotificationMessage :notification="notification">
                            <template #headline>
                                <div v-if="notification.data?.weather" class="flex flex-wrap gap-2">
                                    <span>Summary</span>
                                    <span>{{notification.data?.weather?.temperature}}*F</span>
                                    <span>{{notification.data?.weather?.condition_image}}</span>
                                </div>
                            </template>notification.data?.weather
                            <template #body>
                                <div>
                                    <ul v-for="item in notification.data.articles.splice(0, 3)" class="list-disc ml-4">
                                        <li :href="item.url" target="_blank">{{ item.headline }}</li>
                                    </ul>
                                    ...
                                    <div>
                                        {{formatDate(notification.created_at)}}
                                    </div>
                                </div>
                            </template>
                            <template #actions>
                                <div class="flex gap-3">
                                    <button type="button" class="rounded-md bg-white text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Reply</button>
                                    <button type="button" class="rounded-md bg-white text-sm font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Dismiss</button>
                                    <button type="button" class="rounded-md bg-white text-sm font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Mark as Read</button>
                                </div>
                            </template>
                        </NotificationMessage>

                        <NotificationToast :notification="notification" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NotificationWithAction from '@/Components/Spork/Atoms/NotificationWithAction.vue';
import NotificationMessage from "@/Components/Spork/Atoms/NotificationMessage.vue";
import NotificationToast from "@/Components/Spork/Atoms/NotificationToast.vue";
const page = usePage();

const formatDate = (date) => {
    return dayjs(date).format("MMM D, YYYY hh:mm A")
};
</script>
