<script setup>
import ContextMenuItem from "@/Components/ContextMenus/ContextMenuItem.vue";

import Manage from "@/Layouts/Manage.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import FileOrFolder from "@/Components/Spork/FileOrFolder.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import DialogModal from "../../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/DialogModal.vue";
import {ref, watch} from "vue";
import WeatherHeader from "@/Pages/Petoskey/WeatherHeader.vue";
import dayjs from "dayjs";
const {
    title,
    notifications,
} = defineProps({
    title: String,
    notifications: Array
});
const now = ref(dayjs());
const updateSettings = async () => {
    const response = await axios.post('/api/notification-settings', {
        database: this.database,
        mail: this.mail,
        webhook: this.webhook,
        broadcast: this.broadcast,
    });
    // handle response
}
</script>

<template>
  <AppLayout :title="title">
      <!-- Notification management -->
      <div class="bg-white dark:bg-stone-950 rounded shadow p-4 m-4">


      </div>
  </AppLayout>
</template>

<style scoped>

</style>
