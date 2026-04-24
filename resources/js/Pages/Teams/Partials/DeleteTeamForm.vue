<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

const props = defineProps({ team: Object });

const confirmingTeamDeletion = ref(false);
const form = useForm({});

const confirmTeamDeletion = () => {
    confirmingTeamDeletion.value = true;
};

const deleteTeam = () => {
    form.delete(route('teams.destroy', props.team), { errorBag: 'deleteTeam' });
};
</script>

<template>
    <ActionSection>
        <template #title>Delete Team</template>
        <template #description>Permanently delete this team.</template>

        <template #content>
            <div class="max-w-xl text-sm text-stone-600 dark:text-stone-400">
                Once a team is deleted, all of its resources and data will be permanently deleted. Before deleting this team, please download any data or information regarding this team that you wish to retain.
            </div>

            <div class="mt-5">
                <GlassButton variant="destructive" @click="confirmTeamDeletion">Delete Team</GlassButton>
            </div>

            <GlassModal :open="confirmingTeamDeletion" title="Delete Team" size="sm" @close="confirmingTeamDeletion = false">
                <p class="text-sm text-stone-700 dark:text-stone-200">
                    Are you sure you want to delete this team? Once a team is deleted, all of its resources and data will be permanently deleted.
                </p>
                <template #footer>
                    <GlassButton variant="secondary" @click="confirmingTeamDeletion = false">Cancel</GlassButton>
                    <GlassButton variant="destructive" :disabled="form.processing" @click="deleteTeam">Delete Team</GlassButton>
                </template>
            </GlassModal>
        </template>
    </ActionSection>
</template>
