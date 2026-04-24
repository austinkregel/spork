<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const props = defineProps({
    team: Object,
    permissions: Object,
});

const form = useForm({
    name: props.team?.name ?? '',
});

const updateTeamName = () => {
    form.put(route('teams.update', props.team), {
        errorBag: 'updateTeamName',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateTeamName">
        <template #title>
            Team Name
        </template>

        <template #description>
            The team's name and owner information.
        </template>

        <template #form>
            <div class="col-span-6">
                <div class="block text-sm font-medium text-stone-700 dark:text-stone-200">Team Owner</div>

                <div class="flex items-center mt-2">
                    <img class="w-12 h-12 rounded-full object-cover ring-1 ring-stone-200 dark:ring-stone-700" :src="team.owner.profile_photo_url" :alt="team.owner.name">

                    <div class="ml-4 leading-tight">
                        <div class="text-stone-900 dark:text-stone-100">{{ team.owner.name }}</div>
                        <div class="text-stone-600 dark:text-stone-300 text-sm">
                            {{ team.owner.email }}
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
                        :disabled="! permissions.canUpdateTeam"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>
            </div>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </ActionMessage>

            <GlassButton type="submit" :disabled="form.processing">
                Save
            </GlassButton>
        </template>
    </FormSection>
</template>
