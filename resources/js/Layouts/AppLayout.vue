<script setup>
import { ref, computed } from 'vue';
import {Head, Link, router, usePage} from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';

import ApplicationNavigation from "@/Components/ApplicationNavigation.vue";
import ApplicationUserNavigation from "@/Components/ApplicationUserNavigation.vue";
const page = usePage()
defineProps({
    title: String,
    navigation: Array,
    subNavigation: Array,
  current_navigation: Object,
});

const logout = () => {
  router.post(route('logout'));
};


const notificationCount = computed(() => {
  return (page.props.unread_email_count ?? 0) + page.props.notification_count
});

const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="min-h-screen">
        <Head :title="title" />
        <Banner />
        <ApplicationNavigation />

        <div class="lg:pl-20 xl:pl-64 min-h-screen">
            <ApplicationUserNavigation
                :user="user"
                :notifications="page.props.notifications"
                :notification-count="notificationCount"
            />

            <main style="max-height: calc(100vh - 65px); min-height: calc(100vh - 65px);">
                <slot/>
            </main>
        </div>

        <audio id="glitch-sound" src="/sounds/glitch-in-the-matrix-600.ogg" preload="auto" type="audio/ogg" />
        <audio id="finished-sound" src="/sounds/just-saying-593.ogg" preload="auto" type="audio/ogg" />
        <audio id="error-sound" src="/sounds/relentless-572.ogg" preload="auto" type="audio/ogg" />
        <audio id="notification-sound" src="/sounds/swiftly-610.ogg" preload="auto" type="audio/ogg" />
        <audio id="success-sound" src="/sounds/i-did-it-message-tone.ogg" preload="auto" type="audio/ogg" />
        <audio id="achievement-sound" src="/sounds/achievement-message-tone.ogg" preload="auto" type="audio/ogg" />
    </div>
</template>
