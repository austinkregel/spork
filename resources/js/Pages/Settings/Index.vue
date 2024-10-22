<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {usePage} from "@inertiajs/vue3";

const page = usePage()
</script>
<template>
    <AppLayout>

        <template #default>
            <div>
                <div class="py-4" v-for="(data, index) in page.props.settings.configs">
                    <div class="text-2xl py-2">{{index}}</div>

                    <div v-if="typeof data !== 'string'" v-for="(value, key) in data">{{index}}.{{ key }}: {{ value }}</div>

                    <div v-else>{{index}}: {{ data }}</div>
                </div>

                <pre>{{ page.props.settings.configs }}</pre>
            </div>
        </template>
    </AppLayout>
</template>

<style scoped>

</style>
