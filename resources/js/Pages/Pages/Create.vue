<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateTeamForm from '@/Pages/Teams/Partials/CreateTeamForm.vue';
import SporkInput from "@/Components/Spork/SporkInput.vue";
import SporkLabel from "@/Components/Spork/SporkLabel.vue";
import {ref} from "vue";
import FormSection from "../../Components/FormSection.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";

const form = ref({
    title: '',
    uri: '',
    slug: '',
    route: '',
    middleware: '',
    subtitle: '',
    excerpt: '',
    content: '',
    view: '',
    redirect: '',
    is_active: '',
    sort_order: '',
    published_at: ''
});
</script>

<template>
    <AppLayout title="Create Team">
        <template #header>
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                Create Team
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <!-- Build a form using the Sporkinput component, SporkLabel component, and SporkSelect component to create a form that allows our user to create pages for their projects -->
                <FormSection>
                    <template #title>
                        <span>Thing</span>
                    </template>
                    <template #description>
                        <span>Thing</span>
                    </template>
                    <!-- Label, then input-->
                    <template #form>
                            <div class="w-full">
                                <spork-label for="title" class="block text-sm font-medium">Title</spork-label>
                                <spork-input v-model="form.title" type="text" name="title" id="title" />
                            </div>
                        <div class="w-full">
                                <spork-label for="uri" class="block text-sm font-medium">URI</spork-label>
                                <spork-input v-model="form.uri" type="text" name="uri" id="uri" />
                            </div>
                        <div class="w-full">
                                <spork-label for="slug" class="block text-sm font-medium">Slug</spork-label>
                                <spork-input v-model="form.slug" type="text" name="slug" id="slug" />
                            </div>
                        <div class="w-full">
                                <spork-label for="route" class="block text-sm font-medium">Route</spork-label>
                                <spork-input v-model="form.route" type="text" name="route" id="route" />
                            </div>
                        <div class="w-full">
                                <spork-label for="middleware" class="block text-sm font-medium">Middleware</spork-label>
                                <spork-input v-model="form.middleware" type="text" name="middleware" id="middleware" />
                            </div>
                        <div class="w-full">
                                <spork-label for="subtitle" class="block text-sm font-medium">Subtitle</spork-label>
                                <spork-input v-model="form.subtitle" type="text" name="subtitle" id="subtitle" />
                            </div>
                        <div class="w-full">
                                <spork-label for="excerpt" class="block text-sm font-medium">Excerpt</spork-label>
                                <spork-input v-model="form.excerpt" type="text" name="excerpt" id="excerpt" />
                            </div>
                            <div>
                                <spork-label for="content" class="block text-sm font-medium">Content</spork-label>
                                <spork-input v-model="form.content" type="text" name="content" id="content" />
                            </div>
                            <div>
                                <spork-label for="view" class="block text-sm font-medium">View</spork-label>
                                <spork-input v-model="form.view" type="text" name="view" id="view" />
                            </div>
                            <div>
                                <spork-label for="redirect" class="block text-sm font-medium">Redirect</spork-label>
                                <spork-input v-model="form.redirect" type="text" name="redirect" id="redirect" />
                            </div>
                            <div>
                                <spork-label for="is_active" class="block text-sm font-medium">Is Active</spork-label>
                                <spork-input v-model="form.is_active" type="text" name="is_active" id="is_active" />
                            </div>
                            <div>
                                <spork-label for="sort_order" class="block text-sm font-medium">Sort Order</spork-label>
                                <spork-input v-model="form.sort_order" type="text" name="sort_order" id="sort_order" />
                            </div>
                            <div>
                                <spork-label for="published_at" class="block text-sm font-medium">Published At</spork-label>

                                <spork-input v-model="form.published_at" type="text" name="published_at" id="published_at" />
                            </div>
                    </template>

                    <template #actions>
                        <div class="flex flex-wrap gap-4">
                            <spork-button secondary>cancel</spork-button>
                            <spork-button primary>Save</spork-button>
                        </div>
                    </template>
                </FormSection>
            </div>
        </div>
    </AppLayout>
</template>
