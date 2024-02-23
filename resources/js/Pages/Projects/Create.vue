<template>
    <AppLayout title="Dashboard">
        <div class="w-full border-b dark:border-slate-700 dark:bg-stone-950">
            <div  class="max-w-7xl mx-auto px-8 py-4 flex items-center gap-2 font-semibold text-2xl text-stone-800 dark:text-stone-200 leading-tight">
                <Link href="/-/projects" class="underline">
                    Projects
                </Link>
                <ChevronRightIcon class="h-5 w-5 flex-shrink-0 text-stone-400" aria-hidden="true" />
                <span>Create</span>
            </div>
        </div>

        <div class="max-w-4xl w-full mx-auto py-8 px-4  gap-4">
            <div class="rounded-lg p-4 bg-stone-300 dark:bg-stone-800 shadow-lg">
                <form @submit.prevent="createProject">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-stone-800 tracking-wider dark:text-stone-200">Name</label>
                        <SporkInput v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                    </div>
                    <pre>{{ errors }}</pre>
                    <button
                        class="inline-flex items-center border shadow-sm font-medium rounded-md focus:outline-none px-2 py-1"
                        type="submit"
                    >
                        Create
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import {usePage, Link, router} from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { ChevronRightIcon, PlusIcon } from "@heroicons/vue/24/solid";
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import {reactive, ref} from "vue";

const $page = usePage()

const { data, paginator, errors } = defineProps({
    data: Array,
    paginator: Object,
    errors: Object
})

const form = reactive({
    name: '',
    settings: {},
    team_id: $page.props.auth.user.current_team_id
})

const createProject = () => {
    router.post('/api/crud/projects', form);
}

</script>
