<script setup>
import ContextMenuItem from "@/Components/ContextMenus/ContextMenuItem.vue";

import Manage from "@/Layouts/Manage.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import FileOrFolder from "@/Components/Spork/FileOrFolder.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import DialogModal from "../../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/DialogModal.vue";
import {ref, watch} from "vue";
const {
    title,
    notifications,
} = defineProps({
    title: String,
    notifications: Array
});

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
      <pre class="min-h-28 min-w-full bg-white text-black">{{ notifications }}</pre>
      <form @submit.prevent="updateSettings">
          <label>
              <input type="checkbox" v-model="database">
              Database
          </label>
          <label>
              <input type="checkbox" v-model="mail">
              Mail
          </label>
          <label>
              <input type="checkbox" v-model="webhook">
              Webhook
          </label>
          <label>
              <input type="checkbox" v-model="broadcast">
              Broadcast
          </label>
          <button type="submit">Save</button>
      </form>
  </AppLayout>
</template>

<style scoped>

</style>
