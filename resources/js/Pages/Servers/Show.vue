<template>
    <AppLayout title="Dashboard">
        <div class="flex flex-wrap gap-4 m-4">
            <div class="w-full font-medium text-stone-600 dark:text-stone-300 uppercase ml-4">Server</div>

            <div class="grid grid-cols-1 max-w-5xl mx-auto gap-4 w-full">
                <div>{{server.name}}</div>
                <div>{{server.status}}</div>
                <div>{{server.memory}}</div>
                <div>{{server.vcpu}}</div>
                <div>{{server.os}}</div>

                <div>{{server.credential.name}}</div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import {
    TrashIcon,
    ArrowTopRightOnSquareIcon ,
    DocumentDuplicateIcon,
    PencilIcon,
    UserPlusIcon
} from '@heroicons/vue/24/outline';
import AppLayout from "@/Layouts/AppLayout.vue";
import ContextMenu from "@/Components/ContextMenus/ContextMenu.vue";
import { Link } from '@inertiajs/vue3'
import LaravelVuePagination from "@/Components/Spork/LaravelVuePagination.vue";
import DynamicIcon from "@/Components/DynamicIcon.vue";
import Status from "@/Components/Spork/Atoms/Status.vue";

const { server, pagination } = defineProps({
    server: Object,
    pagination: Object,
})
const date = () => {}

</script>
