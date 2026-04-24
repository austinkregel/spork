<script setup>
import { useForm } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const form = useForm({
    name: '',
});

const createTeam = () => {
    form.post(route('teams.store'), {
        errorBag: 'createTeam',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="createTeam">
        <template #title>
            Team Details
        </template>

        <template #description>
            Create a new team to collaborate with others on projects.
        </template>

        <template #form>
            <div class="col-span-6">
                <div class="block text-sm font-medium text-stone-700 dark:text-stone-200">Team Owner</div>

                <div class="flex items-center mt-2">
                    <img class="object-cover w-12 h-12 rounded-full ring-1 ring-stone-200 dark:ring-stone-700" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">

                    <div class="ml-4 leading-tight">
                        <div class="text-stone-900 dark:text-stone-100">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm text-stone-600 dark:text-stone-300">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <GlassField v-slot="{ id, describedby, invalid }" label="Team Name" :error="form.errors.name">
                    <GlassInput
                        :id="id"
                        v-model="form.name"
                        type="text"
                        autofocus
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>
            </div>
        </template>

        <template #actions>
            <GlassButton type="submit" :disabled="form.processing">
                Create
            </GlassButton>
        </template>
    </FormSection>
</template>
